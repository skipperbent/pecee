<?php

namespace Pecee\DB\Schema;

use Pecee\DB\Pdo;
use Pecee\DB\PdoHelper;

class Table
{

    const TYPE_CREATE = 'create';
    const TYPE_MODIFY = 'modify';
    const TYPE_ALTER = 'alter';

    const ENGINE_INNODB = 'InnoDB';
    const ENGINE_MEMORY = 'MEMORY';
    const ENGINE_ARCHIVE = 'ARCHIVE';
    const ENGINE_CSV = 'CSV';
    const ENGINE_BLACKHOLE = 'BLACKHOLE';
    const ENGINE_MRG_MYISAM = 'MRG_MYISAM';
    const ENGINE_MYISAM = 'MyISAM';

    public static $ENGINES = [
        self::ENGINE_INNODB,
        self::ENGINE_ARCHIVE,
        self::ENGINE_CSV,
        self::ENGINE_BLACKHOLE,
        self::ENGINE_MEMORY,
        self::ENGINE_MRG_MYISAM,
        self::ENGINE_MYISAM,
    ];

    /**
     * @var array
     */
    protected $columns = [];
    protected $name;
    protected $engine;

    public function __construct($name = null)
    {
        $this->name = $name;
        $this->engine = self::ENGINE_INNODB;
    }

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Create timestamp columns
     * @return static $this
     */
    public function timestamps()
    {
        $this->column('updated_at')->datetime()->nullable()->index();
        $this->column('created_at')->datetime()->index();

        return $this;
    }

    /**
     * @param $name
     * @return Column
     */
    public function column($name)
    {
        $column = new Column($this->name);
        $column->setName($name);

        $this->columns[] = $column;

        return $column;
    }

    public function getPrimary($default = null)
    {
        /* @var $column Column */
        foreach ($this->columns as $column) {
            if ($column->getIndex() === Column::INDEX_PRIMARY) {
                return $column;
            }
        }

        return $default;
    }

    public function getColumnByIndex($index)
    {
        return $this->columns[$index];
    }

    public function getColumnNames($lower = false, $excludePrimary = false)
    {
        $names = [];
        /* @var $column Column */
        foreach ($this->columns as $column) {
            if ($excludePrimary && $column->getIndex() === Column::INDEX_PRIMARY) {
                continue;
            }
            if ($lower) {
                $names[] = strtolower($column->getName());
            } else {
                $names[] = $column->getName();
            }
        }

        return $names;
    }

    public function getColumn($name, $strict = false)
    {
        /* @var $column Column */
        foreach ($this->columns as $column) {
            if (($strict === true && $column->getName() === $name) || ($strict === false && strtolower($column->getName()) === strtolower($name))) {
                return $column;
            }
        }

        return null;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setEngine($engine)
    {
        if (in_array($engine, self::$ENGINES) === false) {
            throw new \InvalidArgumentException('Invalid or unsupported engine');
        }
        $this->engine = $engine;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function exists()
    {
        return (Pdo::getInstance()->value('SHOW TABLES LIKE ?', [$this->name]) !== false);
    }

    public function columnExists($name)
    {
        return (Pdo::getInstance()->value('SHOW COLUMNS FROM `' . $this->name . '` LIKE ?', [$name]) !== false);
    }

    protected function getColumnQuery($type, Column $column)
    {
        $length = '';
        if ($column->getLength()) {
            $length = '(' . $column->getLength() . ')';
        }

        $modify = false;
        $modifyType = '';

        if ($type === static::TYPE_ALTER) {

            $modifyType = 'ADD ';

            if ($this->columnExists($column->getName())) {
                $modify = true;
            }
        }

        $query = sprintf('%s COLUMN `%s` %s%s %s', (($modify) ? 'MODIFY' : $modifyType), $column->getName(), $column->getType(), $length, $column->getAttributes());

        $query .= (!$column->getNullable()) ? ' NOT null' : ' null';

        if ($column->getDefaultValue()) {
            $query .= PdoHelper::formatQuery(' DEFAULT %s', [$column->getDefaultValue()]);
        }

        if ($column->getComment()) {
            $query .= PdoHelper::formatQuery(' COMMENT %s', [$column->getComment()]);
        }

        if ($column->getAfter()) {
            $query .= sprintf(' AFTER `%s`', $column->getAfter());
        }

        if ($column->getIncrement()) {
            $query .= ' AUTO_INCREMENT';
        }

        if ($column->getIndex()) {

            if ($modify === true) {
                $this->dropIndex([
                    $column->getName(),
                ]);
            }

            $query .= sprintf(', %1$s %2$s (`%3$s`)', $modifyType, $column->getIndex(), $column->getName());
        }

        if ($column->getRelationTable() !== null && $column->getRelationColumn() !== null) {

            if ($modify === true) {
                $this->dropForeign([
                    $column->getName(),
                ]);
            }

            $query .= sprintf(', %1$s CONSTRAINT `%2$s` FOREIGN KEY(`%3$s`) REFERENCES `%4$s`(`%5$s`) ON UPDATE %6$s ON DELETE %7$s',
                $modifyType,
                $this->name,
                $column->getName(),
                $column->getRelationTable(),
                $column->getRelationColumn(),
                $column->getRelationUpdateType(),
                $column->getRelationDeleteType());
        }

        return trim($query);
    }

    /**
     * Create table
     */
    public function create()
    {
        if ($this->exists()) {
            return;
        }

        $queries = [];

        /* @var $column Column */
        foreach ($this->columns as $column) {

            if ($column->getDrop() === true) {
                continue;
            }

            $queries[] = $this->getColumnQuery(static::TYPE_CREATE, $column);
        }

        if (count($queries) > 0) {
            $sql = sprintf('CREATE TABLE `%s` (%s) ENGINE = %s;', $this->name, join(',', $queries), $this->engine);
            Pdo::getInstance()->nonQuery($sql);
        }
    }

    public function alter()
    {
        if ($this->exists()) {

            $queries = [];

            /* @var $column Column */
            foreach ($this->columns as $column) {

                if ($column->getDrop() === true) {
                    if ($this->columnExists($column->getName())) {
                        Pdo::getInstance()->nonQuery(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $this->name, $column->getName()));
                    }
                    continue;
                }

                $queries[] = $this->getColumnQuery(static::TYPE_ALTER, $column);
            }

            if (count($queries) > 0) {
                $sql = sprintf('ALTER TABLE `%s` %s', $this->name, join(',', $queries));
                Pdo::getInstance()->nonQuery($sql);
            }
        }
    }

    public function rename($name)
    {
        Pdo::getInstance()->nonQuery('RENAME TABLE `' . $this->name . '` TO `' . $name . '`;');
        $this->name = $name;

        return $this;
    }

    /**
     * @param string|array $indexes
     * @return static $this
     */
    public function dropIndex($indexes)
    {
        $indexes = (array)$indexes;
        foreach ($indexes as $index) {
            try {
                Pdo::getInstance()->nonQuery('ALTER TABLE `' . $this->name . '` DROP INDEX `' . $index . '`');
            } catch (\PDOException $e) {

            }
        }

        return $this;
    }

    public function createIndex(array $columns = null, $type = Column::INDEX_INDEX, $name = null)
    {
        $type = ($type === Column::INDEX_INDEX) ? '' : $type;
        $columns = ($columns === null) ? [$name] : $columns;

        if($name !== null) {

            $this->dropIndex([
                $name,
            ]);

            $name = '`' . $name . '`';

        } else {
            $name = '';
        }

        $query = sprintf('ALTER TABLE `%s` ADD %s INDEX %s (%s);', $this->name, $type, $name, PdoHelper::joinArray($columns, true));
        Pdo::getInstance()->nonQuery($query);

        return $this;
    }

    public function createFulltext(array $columns = null, $name = null)
    {
        return $this->createIndex($columns, Column::INDEX_FULLTEXT, $name);
    }

    public function fulltext(array $columns = null, $name = null)
    {
        return $this->createFulltext($columns, $name);
    }

    public function dropPrimary()
    {
        Pdo::getInstance()->nonQuery('ALTER TABLE `' . $this->name . '` DROP PRIMARY KEY');

        return $this;
    }

    public function dropForeign(array $indexes)
    {
        foreach ($indexes as $index) {
            Pdo::getInstance()->nonQuery('ALTER TABLE `' . $this->name . '` DROP FOREIGN KEY `' . $index . '`');
        }

        return $this;
    }

    public function dropIfExists()
    {
        if ($this->exists() === true) {
            $this->drop();
        }

        return $this;
    }

    public function truncate()
    {
        Pdo::getInstance()->nonQuery('TRUNCATE TABLE `' . $this->name . '`;');
    }

    public function drop()
    {
        Pdo::getInstance()->nonQuery('DROP TABLE `' . $this->name . '`;');
    }

}