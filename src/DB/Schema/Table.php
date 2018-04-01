<?php

namespace Pecee\DB\Schema;

use Pecee\DB\Pdo;
use Pecee\DB\PdoHelper;
use Pecee\Exceptions\InvalidArgumentException;

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

    public function __construct(?string $name = null)
    {
        $this->name = $name;
        $this->engine = static::ENGINE_INNODB;
    }

    /**
     * Set column name
     *
     * @param string $name
     * @return static
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Add timestamp columns
     *
     * @return static
     */
    public function timestamps(): self
    {
        $this->column('updated_at')->datetime()->nullable()->index();
        $this->column('created_at')->datetime()->index();

        return $this;
    }

    /**
     * Add new column
     *
     * @param string $name
     * @return Column
     */
    public function column(string $name): Column
    {
        $column = new Column($this->name);
        $column->setName($name);

        $this->columns[] = $column;

        return $column;
    }

    public function getPrimaryColumn(): ?Column
    {
        /* @var $column Column */
        foreach ($this->columns as $column) {
            if ($column->getIndex() === Column::INDEX_PRIMARY) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Get column by index
     *
     * @param string $index
     * @return Column|null
     */
    public function getColumnByIndex(string $index): ?Column
    {
        return $this->columns[$index] ?? null;
    }

    /**
     * Get column names
     *
     * @param bool $lower
     * @param bool $excludePrimary
     * @return array
     */
    public function getColumnNames(bool $lower = false, bool $excludePrimary = false): array
    {
        $names = [];
        /* @var $column Column */
        foreach ($this->columns as $column) {
            if ($excludePrimary === true && $column->getIndex() === Column::INDEX_PRIMARY) {
                continue;
            }

            $names[] = ($lower === true) ? strtolower($column->getName()) : $column->getName();
        }

        return $names;
    }

    /**
     * Get column
     *
     * @param string $name
     * @param bool $strict
     * @return Column|null
     */
    public function getColumn(string $name, bool $strict = false): ?Column
    {
        /* @var $column Column */
        foreach ($this->columns as $column) {
            if (($strict === true && $column->getName() === $name) || ($strict === false && strtolower($column->getName()) === strtolower($name))) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Get all columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set table name
     * @param string $name
     * @return static
     */
    public function setName(string $name): self
    {
        return $this->name($name);
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param $engine
     * @throws \InvalidArgumentException
     * @return static
     */
    public function setEngine(string $engine): self
    {
        if (\in_array($engine, static::$ENGINES, true) === false) {
            throw new \InvalidArgumentException('Invalid or unsupported engine');
        }

        $this->engine = $engine;

        return $this;
    }

    /**
     * @return string
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * Check if table exist.
     * @return bool
     */
    public function exists(): bool
    {
        return (Pdo::getInstance()->value('SHOW TABLES LIKE ?', [$this->name]) !== false);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function columnExists(string $name): bool
    {
        return (Pdo::getInstance()->value('SHOW COLUMNS FROM `' . $this->name . '` LIKE ?', [$name]) !== false);
    }

    /**
     * Generates column query
     *
     * @param string $type
     * @param Column $column
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function generateColumnQuery(string $type, Column $column): ?string
    {
        if ($column->getDrop() === true) {
            if ($this->columnExists($column->getName())) {
                Pdo::getInstance()->nonQuery(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $this->name, $column->getName()));
            }

            return null;
        }

        if ($column->getIndex() === null && $column->getRelationTable() === null) {
            return null;
        }

        $query = '';
        $alterColumn = '';
        $modify = false;
        $columnType = $column->getType();

        if ($columnType !== null) {

            if ($type === static::TYPE_ALTER) {

                $alterColumn = 'ADD COLUMN';

                if ($this->columnExists($column->getName())) {
                    $alterColumn = 'CHANGE COLUMN';
                    $modify = true;
                }
            }

            $query .= sprintf('%s `%s` %s%s %s%s%s%s%s%s, ',
                $alterColumn,
                $column->getName(),
                $columnType,
                ($column->getLength() ? " ({$column->getLength()})" : ''),
                $column->getAttributes(),
                ($column->getNullable() === false ? ' NOT null' : ' null'),
                ($column->getDefaultValue() !== null) ? PdoHelper::formatQuery(' DEFAULT %s', [$column->getDefaultValue()]) : '',
                ($column->getComment() !== null) ? PdoHelper::formatQuery(' COMMENT %s', [$column->getComment()]) : '',
                ($column->getAfter() !== null) ? " AFTER `{$column->getAfter()}`" : '',
                ($column->getIncrement() === true ? ' AUTO_INCREMENT' : '')
            );
        }

        if ($column->getIndex() !== null) {
            if ($modify === true) {
                $this->dropIndex([
                    $column->getName(),
                ]);
            }

            $query .= sprintf('%1$s %2$s `%3$s`(`%3$s`), ',
                (($type === static::TYPE_ALTER) ? 'ADD ' : ''),
                $column->getIndex(),
                $column->getName()
            );
        }

        if ($column->getRelationTable() !== null && $column->getRelationColumn() !== null) {

            if ($column->getRemoveRelation() === true) {

                if ($type !== static::TYPE_ALTER) {
                    throw new InvalidArgumentException('You cannot remove a relation when creating a new table.');
                }

                $query .= sprintf('DROP FOREIGN KEY `%s`, ', $column->getRelationKey());

            } else {

                if ($this->foreignExist($column->getRelationKey()) === true) {
                    $this->dropForeign([
                        $column->getRelationKey(),
                    ]);
                }

                $query .= sprintf('%1$s CONSTRAINT `%2$s` FOREIGN KEY (`%3$s`) REFERENCES `%4$s`(`%5$s`) ON UPDATE %6$s ON DELETE %7$s, ',
                    ($type === static::TYPE_ALTER) ? 'ADD' : '',
                    $column->getRelationKey(),
                    $column->getName(),
                    $column->getRelationTable(),
                    $column->getRelationColumn(),
                    $column->getRelationUpdateType(),
                    $column->getRelationDeleteType());

            }

        }

        return trim($query, ', ');
    }

    /**
     * Create table
     * @throws \InvalidArgumentException
     */
    public function create(): void
    {
        if ($this->exists() === true) {
            return;
        }

        $queries = [];

        /* @var $column Column */
        foreach ($this->columns as $column) {
            $query = $this->generateColumnQuery(static::TYPE_CREATE, $column);
            if (trim($query) !== '') {
                $queries[] = $query;
            }
        }

        if (\count($queries) > 0) {
            $sql = sprintf('CREATE TABLE `%s` (%s) ENGINE = %s;', $this->name, implode(',', $queries), $this->engine);
            Pdo::getInstance()->nonQuery($sql);
        }
    }

    /**
     * Modify table
     * @throws \InvalidArgumentException
     */
    public function alter(): void
    {
        if ($this->exists() === false) {
            return;
        }

        $queries = [];

        /* @var $column Column */
        foreach ($this->columns as $column) {
            $query = $this->generateColumnQuery(static::TYPE_ALTER, $column);
            if (trim($query) !== '') {
                $queries[] = $query;
            }
        }

        if (\count($queries) > 0) {
            $sql = sprintf('ALTER TABLE `%s` %s', $this->name, implode(',', $queries));
            Pdo::getInstance()->nonQuery($sql);
        }

    }

    /**
     * Rename table
     *
     * @param string $name
     * @return static
     */
    public function rename(string $name): self
    {
        Pdo::getInstance()->nonQuery("RENAME TABLE `{$this->name}` TO `$name`;");
        $this->name = $name;

        return $this;
    }

    /**
     * Remove indexes
     *
     * @param array ...$indexes
     * @return static
     */
    public function dropIndex(...$indexes): self
    {
        $indexes = (array)$indexes;
        foreach ($indexes as $index) {
            try {
                Pdo::getInstance()->nonQuery(sprintf('ALTER TABLE `%s` DROP INDEX `%s`;', $this->name, $index));
            } catch (\PDOException $e) {

            }
        }

        return $this;
    }

    /**
     * Create index
     *
     * @param array|null $columns
     * @param string $type
     * @param string|null $name
     * @return static
     */
    public function createIndex(array $columns = null, string $type = Column::INDEX_INDEX, ?string $name = null): self
    {
        $type = ($type === Column::INDEX_INDEX) ? '' : $type;
        $columns = $columns ?? [$name];

        if ($name !== null) {
            $this->dropIndex([$name,]);
            $name = '`' . $name . '`';
        } else {
            $name = '';
        }

        $query = sprintf
        (
            'ALTER TABLE `%s` ADD %s %s (%s);',
            $this->name,
            $type,
            $name,
            PdoHelper::joinArray($columns, true)
        );

        Pdo::getInstance()->nonQuery($query);

        return $this;
    }

    /**
     * @param array|null $columns
     * @param string|null $name
     * @return static
     */
    public function createFulltext(array $columns = null, ?string $name = null): self
    {
        return $this->createIndex($columns, Column::INDEX_FULLTEXT, $name);
    }

    /**
     * Create new fulltext index
     *
     * @param array|null $columns
     * @param string|null $name
     * @return static
     */
    public function fulltext(array $columns = null, ?string $name = null): self
    {
        return $this->createFulltext($columns, $name);
    }

    /**
     * Drop primary key
     *
     * @return static
     */
    public function dropPrimary(): self
    {
        Pdo::getInstance()->nonQuery("ALTER TABLE `{$this->name}` DROP PRIMARY KEY");

        return $this;
    }

    /**
     * Check if foreign key exists.
     *
     * @param string $name
     * @return bool
     */
    public function foreignExist(string $name): bool
    {
        return (int)Pdo::getInstance()->value('SELECT COUNT(`TABLE_NAME`) FROM information_schema.`TABLE_CONSTRAINTS` WHERE `CONSTRAINT_NAME` = ? && `TABLE_NAME` = ?', [$name, $this->name]) <= 0;
    }

    /**
     * Drop foreign keys
     *
     * @param array $indexes
     * @return static
     */
    public function dropForeign(array $indexes): self
    {
        foreach ($indexes as $key => $index) {

            // Skip if the foreign-key is already removed.
            if ($this->foreignExist($index) === true) {
                unset($indexes[$key]);
                continue;
            }

            $indexes[$key] = "DROP FOREIGN KEY `$index`";
        }

        // Execute query
        Pdo::getInstance()->nonQuery(
            sprintf
            (
                'ALTER TABLE %s %s',
                $this->name,
                implode(', ', $indexes)
            )
        );

        return $this;
    }

    /**
     * Add foreign key
     *
     * @param string $keyName
     * @param array $referenceColumns
     * @param array $foreignTable ['table' => ['column1', 'column2']
     * @param string $onUpdate Relation type constraint on update
     * @param string $onDelete Relation type constraint on delete
     * @return static
     * @throws \InvalidArgumentException
     */
    public function addForeign(string $keyName, array $referenceColumns, array $foreignTable, string $onUpdate = Column::RELATION_RESTRICT, string $onDelete = Column::RELATION_CASCADE): self
    {
        /* --- Parse foreign table --- */

        $first = reset($foreignTable);

        if ($first === false) {
            throw new \InvalidArgumentException('Misformed referenceTable parameter.');
        }

        $foreignTableName = key($first);

        if (\is_string($foreignTableName) === false) {
            throw new \InvalidArgumentException('Misformed referenceTable parameter. Failed to parse table.');
        }

        $foreignColumns = array_values((array)$first[$foreignTableName]);

        $query = sprintf
        (
            'ALTER TABLE %1$s ADD CONSTRAINT `%2$s` FOREIGN KEY (`%3$s`) REFERENCES `%4$s`(`%5$s`) ON UPDATE %6$s ON DELETE %7$s',
            $this->name,
            $keyName,
            PdoHelper::joinArray($referenceColumns),
            $foreignTableName,
            PdoHelper::joinArray($foreignColumns),
            $onUpdate,
            $onDelete
        );

        Pdo::getInstance()->nonQuery($query);

        return $this;
    }

    /**
     * Drop table if it exists.
     *
     * @return static
     */
    public function dropIfExists(): self
    {
        if ($this->exists() === true) {
            $this->drop();
        }

        return $this;
    }

    /**
     * Truncate table
     *
     * @return static
     */
    public function truncate(): self
    {
        Pdo::getInstance()->nonQuery(sprintf('TRUNCATE TABLE `%s`', $this->name));

        return $this;
    }

    /**
     * Drop table
     *
     * @return static
     */
    public function drop(): self
    {
        Pdo::getInstance()->nonQuery(sprintf('DROP TABLE `%s`', $this->name));

        return $this;
    }

}