<?php

namespace Pecee\DB\Schema;

use InvalidArgumentException;

class Index
{

    public const TYPE_PRIMARY = 'PRIMARY KEY';
    public const TYPE_UNIQUE = 'UNIQUE INDEX';
    public const TYPE_INDEX = 'INDEX';
    public const TYPE_FULLTEXT = 'FULLTEXT INDEX';

    public static array $TYPES = [
        self::TYPE_PRIMARY,
        self::TYPE_UNIQUE,
        self::TYPE_INDEX,
        self::TYPE_FULLTEXT,
    ];

    protected Table $table;
    protected ?string $name = null;
    protected string $type;
    protected array $columns = [];

    public function __construct(Table $table, string $type = self::TYPE_PRIMARY)
    {
        $this->table = $table;
        $this->setType($type);
    }

    public function name(string $name): self
    {
        return $this->setName($name);
    }

    public function type(string $type): self
    {
        return $this->setType($type);
    }

    public function add(string $column, ?int $length = null): self
    {
        return $this->addColumn($column, $length);
    }

    public function columns(array $columns): self
    {
        return $this->setColumns($columns);
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function addColumn(string $column, ?int $length = null): self
    {
        $this->columns[$column] = $length;

        return $this;
    }

    public function setColumnLength(string $column, ?int $length = null): self
    {
        return $this->addColumn($column, $length);
    }

    public function remove(string $column): self
    {
        unset($this->columns[$column]);

        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? implode('_', array_keys($this->columns));
    }

    public function setType(string $type): self
    {
        if (in_array($type, static::$TYPES, true) === false) {
            throw new InvalidArgumentException(sprintf('Invalid index-type. Valid types are: %s', implode(', ', static::$TYPES)));
        }

        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getColumnLength(string $column): ?int
    {
        return $this->columns[$column] ?? null;
    }

    public function exists(): bool
    {
        return ($this->table->getConnection()->value("SHOW INDEX FROM `{$this->table->getName()}` WHERE Key_name = '{$this->getName()}';") !== false);
    }

    /**
     * Remove index
     *
     * @return static
     */
    public function drop(): self
    {
        if ($this->exists() === true) {
            $this->table->getConnection()->nonQuery(sprintf('ALTER TABLE `%s` DROP INDEX `%s`;', $this->table->getName(), $this->getName()));
        }

        return $this;
    }

    public function create(): self
    {
        $this->drop();

        $columns = [];

        foreach ($this->columns as $column => $length) {
            if ($length === null) {
                $columns[] = "`$column`";
                continue;
            }

            $columns[] = "`$column`($length)";
        }

        $query = sprintf
        (
            'ALTER TABLE `%s` ADD %s `%s` (%s);',
            $this->table->getName(),
            $this->type,
            $this->getName(),
            implode(', ', $columns),
        );

        $this->table->getConnection()->nonQuery($query);

        return $this;
    }

    public function setColumns(array $columns): self
    {
        $keys = array_values($columns);
        if (is_string($keys[0]) === true) {
            $fixed = [];
            foreach ($columns as $column) {
                $fixed[$column] = null;
            }
            $columns = $fixed;
        }

        $this->columns = $columns;

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getQuery(): string
    {
        // TODO: implement query
        return '';
    }

}