<?php

namespace Pecee\DB\Schema;

use Pecee\DB\Pdo;
use Pecee\DB\PdoHelper;

class Table
{

    public const TYPE_CREATE = 'create';
    public const TYPE_MODIFY = 'modify';
    public const TYPE_ALTER = 'alter';

    public const ENGINE_INNODB = 'InnoDB';
    public const ENGINE_MEMORY = 'MEMORY';
    public const ENGINE_ARCHIVE = 'ARCHIVE';
    public const ENGINE_CSV = 'CSV';
    public const ENGINE_BLACKHOLE = 'BLACKHOLE';
    public const ENGINE_MRG_MYISAM = 'MRG_MYISAM';
    public const ENGINE_MYISAM = 'MyISAM';

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
        $this->engine = static::ENGINE_INNODB;
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

    /**
     * @param string $engine
     * @throws \InvalidArgumentException
     */
    public function setEngine($engine)
    {
        if (\in_array($engine, static::$ENGINES, true) === false) {
            throw new \InvalidArgumentException('Invalid or unsupported engine');
        }
        $this->engine = $engine;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return bool
     * @throws \PDOException
     */
    public function exists()
    {
        return Pdo::getInstance()->value('SHOW TABLES LIKE ?', [$this->name]) !== false;
    }

    /**
     * @param string $name
     * @return bool
     * @throws \PDOException
     */
    public function columnExists($name)
    {
        return Pdo::getInstance()->value('SHOW COLUMNS FROM `' . $this->name . '` LIKE ?', [$name]) !== false;
    }

    /**
     * @param $type
     * @param Column $column
     * @return string
     * @throws \PDOException
     */
    protected function getColumnQuery($type, Column $column)
    {
        $length = '';
        if ($column->getLength()) {
            $length = '(' . $column->getLength() . ')';
        }

        $modify = false;
        $modifyType = '';

        if ($type === static::TYPE_ALTER) {

            $modifyType = '';

            if ($this->columnExists($column->getName())) {
                $modify = true;
            }
        }

        $query = sprintf('%s `%s` %s%s %s', ($modify === true ? 'MODIFY' : $modifyType), $column->getName(), $column->getType(), $length, $column->getAttributes());

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

            $query .= sprintf(', %1$s CONSTRAINT FOREIGN KEY(`%2$s`) REFERENCES `%3$s`(`%4$s`) ON UPDATE %5$s ON DELETE %6$s',
                $modifyType,
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
     * @throws \PDOException
     */
    public function create() : void
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

        if (\count($queries) > 0) {
            $sql = sprintf('CREATE TABLE `%s` (%s) ENGINE = %s;', $this->name, implode(',', $queries), $this->engine);
            Pdo::getInstance()->nonQuery($sql);
        }
    }

    /**
     * @throws \PDOException
     */
    public function alter() : void
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

            if (\count($queries) > 0) {
                $sql = sprintf('ALTER TABLE `%s` %s', $this->name, implode(',', $queries));
                Pdo::getInstance()->nonQuery($sql);
            }
        }
    }

    /**
     * @param $name
     * @return static $this
     * @throws \PDOException
     */
    public function rename($name) : self
    {
        Pdo::getInstance()->nonQuery('RENAME TABLE `' . $this->name . '` TO `' . $name . '`;');
        $this->name = $name;

        return $this;
    }

    /**
     * @param string|array $indexes
     * @return static $this
     */
    public function dropIndex($indexes) : self
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

    /**
     * @param array|null $columns
     * @param string $type
     * @param string|null $name
     * @return static $this
     * @throws \PDOException
     */
    public function createIndex(array $columns = null, $type = Column::INDEX_INDEX, $name = null) : self
    {
        $type = ($type === Column::INDEX_INDEX) ? '' : $type;
        $columns = $columns ?? [$name];

        if ($name !== null) {

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

    /**
     * @param array|null $columns
     * @param string|null $name
     * @return static
     * @throws \PDOException
     */
    public function createFulltext(array $columns = null, $name = null)
    {
        return $this->createIndex($columns, Column::INDEX_FULLTEXT, $name);
    }

    /**
     * @param array|null $columns
     * @param null $name
     * @return static
     * @throws \PDOException
     */
    public function fulltext(array $columns = null, $name = null) : self
    {
        return $this->createFulltext($columns, $name);
    }

    /**
     * @return static
     * @throws \PDOException
     */
    public function dropPrimary() : self
    {
        Pdo::getInstance()->nonQuery('ALTER TABLE `' . $this->name . '` DROP PRIMARY KEY');

        return $this;
    }

    /**
     * @param array $indexes
     * @return static
     * @throws \PDOException
     */
    public function dropForeign(array $indexes) : self
    {
        foreach ($indexes as $index) {
            Pdo::getInstance()->nonQuery('ALTER TABLE `' . $this->name . '` DROP FOREIGN KEY `' . $index . '`');
        }

        return $this;
    }

    /**
     * @return static $this
     * @throws \PDOException
     */
    public function dropIfExists() : self
    {
        if ($this->exists() === true) {
            $this->drop();
        }

        return $this;
    }

    /**
     * @throws \PDOException
     */
    public function truncate() : void
    {
        Pdo::getInstance()->nonQuery('TRUNCATE TABLE `' . $this->name . '`;');
    }

    /**
     * @throws \PDOException
     */
    public function drop() : void
    {
        Pdo::getInstance()->nonQuery('DROP TABLE `' . $this->name . '`;');
    }

}