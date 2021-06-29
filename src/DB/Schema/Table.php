<?php

namespace Pecee\DB\Schema;

use InvalidArgumentException;
use PDOException;
use Pecee\DB\IDatabase;
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

    public static array $ENGINES = [
        self::ENGINE_INNODB,
        self::ENGINE_ARCHIVE,
        self::ENGINE_CSV,
        self::ENGINE_BLACKHOLE,
        self::ENGINE_MEMORY,
        self::ENGINE_MRG_MYISAM,
        self::ENGINE_MYISAM,
    ];

    protected ?string $name = null;
    protected string $engine;

    /**
     * @var array|Column[]
     */
    protected array $columns = [];
    /**
     * @var array|Index[]
     */
    protected array $indexes = [];

    protected Pdo $connection;

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
        $column = new Column($this);
        $column->setName($name);

        $this->columns[] = $column;

        return $column;
    }

    public function index(?string $name = null, string $type = Index::TYPE_INDEX): Index
    {
        $index = new Index($this, $type);

        if ($name !== null) {
            $index->setName($name);
        }

        $this->addIndex($index);

        return $index;
    }

    public function addIndex(Index $index): self
    {
        $this->indexes[$index->getName()] = $index;

        return $this;
    }

    public function removeIndex(string $name): self
    {
        unset($this->indexes[$name]);

        return $this;
    }

    public function getIndex(string $name): ?Index
    {
        return $this->indexes[$name] ?? null;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getPrimaryColumn(): ?Column
    {
        foreach ($this->columns as $column) {
            $index = $column->getIndex();
            if ($index !== null && $index->getType() === Index::TYPE_PRIMARY) {
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
        foreach ($this->columns as $column) {
            $index = $column->getIndex();
            if ($excludePrimary === true && $index !== null && $index->getType() === Index::TYPE_PRIMARY) {
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
     * @param string $engine
     * @return static
     * @throws InvalidArgumentException
     */
    public function setEngine(string $engine): self
    {
        if (in_array($engine, static::$ENGINES, true) === false) {
            throw new InvalidArgumentException('Invalid or unsupported engine');
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
        return ($this->getConnection()->value('SHOW TABLES LIKE ?', [$this->name]) !== false);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function columnExists(string $name): bool
    {
        return ($this->getConnection()->value("SHOW COLUMNS FROM `{$this->name}` LIKE ?", [$name]) !== false);
    }

    /**
     * Create table
     * @throws InvalidArgumentException
     */
    public function create(): void
    {
        if ($this->exists() === true) {
            return;
        }

        $queries = [];

        foreach ($this->columns as $column) {
            $query = $column->getQuery(static::TYPE_CREATE);
            if ($query !== '') {
                $queries[] = $query;
            }

            unset($this->indexes[$column->getName()]);
        }

        if (count($queries) > 0) {
            $sql = sprintf('CREATE TABLE `%s` (%s) ENGINE = %s;', $this->name, implode(',', $queries), $this->engine);
            $this->getConnection()->nonQuery($sql);

            foreach ($this->indexes as $index) {
                $index->create();
            }
        }
    }

    /**
     * Modify table
     * @throws InvalidArgumentException
     */
    public function alter(): void
    {
        if ($this->exists() === false) {
            return;
        }

        $queries = [];

        foreach ($this->columns as $column) {
            $query = $column->getQuery(static::TYPE_ALTER);
            if ($query !== '') {
                $queries[] = $query;
            }

            unset($this->indexes[$column->getName()]);
        }

        if (count($queries) > 0) {
            $sql = sprintf('ALTER TABLE `%s` %s', $this->name, implode(',', $queries));
            $this->getConnection()->nonQuery($sql);
        }

        foreach ($this->indexes as $index) {
            $index->create();
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
        $this->getConnection()->nonQuery("RENAME TABLE `$this->name` TO `$name`;");
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
        if (count($indexes) === 0) {
            return $this;
        }

        $drop = implode(', ', array_map(static function ($name) {
            return "DROP INDEX `$name`";
        }, $indexes));

        try {
            $this->getConnection()->nonQuery("ALTER TABLE `{$this->name}` $drop;");
        } catch (PDOException $e) {

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
    public function createIndex(array $columns = null, string $type = Index::TYPE_INDEX, ?string $name = null): self
    {
        $type = ($type === Index::TYPE_INDEX) ? '' : $type;
        $columns = $columns ?? [$name];

        if ($name !== null) {
            $this->dropIndex([$name,]);
            $name = "`$name`";
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

        $this->getConnection()->nonQuery($query);

        return $this;
    }

    /**
     * @param array|null $columns
     * @param string|null $name
     * @return static
     */
    public function createFulltext(array $columns = null, ?string $name = null): self
    {
        return $this->createIndex($columns, Index::TYPE_INDEX, $name);
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
        $this->getConnection()->nonQuery("ALTER TABLE `$this->name` DROP PRIMARY KEY");

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
        return ((int)$this->getConnection()->value('SELECT COUNT(`TABLE_NAME`) FROM information_schema.`TABLE_CONSTRAINTS` WHERE `CONSTRAINT_NAME` = ? && `TABLE_NAME` = ?', [$name, $this->name])) > 0;
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
        $this->getConnection()->nonQuery(
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
     * @throws InvalidArgumentException
     */
    public function addForeign(string $keyName, array $referenceColumns, array $foreignTable, string $onUpdate = Column::RELATION_RESTRICT, string $onDelete = Column::RELATION_CASCADE): self
    {
        /* --- Parse foreign table --- */

        $first = reset($foreignTable);

        if ($first === false) {
            throw new InvalidArgumentException('Misformed referenceTable parameter.');
        }

        $foreignTableName = key($first);

        if (is_string($foreignTableName) === false) {
            throw new InvalidArgumentException('Misformed referenceTable parameter. Failed to parse table.');
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

        $this->getConnection()->nonQuery($query);

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
        $this->getConnection()->nonQuery(sprintf('TRUNCATE TABLE `%s`', $this->name));

        return $this;
    }

    /**
     * Drop table
     *
     * @return static
     */
    public function drop(): self
    {
        $this->getConnection()->nonQuery(sprintf('DROP TABLE `%s`', $this->name));

        return $this;
    }

    public function getConnection(): Pdo
    {
        return $this->connection ?? Pdo::getInstance();
    }

    public function setConnection(Pdo $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

}