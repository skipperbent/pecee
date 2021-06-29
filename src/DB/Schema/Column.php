<?php

namespace Pecee\DB\Schema;

use InvalidArgumentException;
use Pecee\DB\PdoHelper;

class Column
{
    public const RELATION_RESTRICT = 'RESTRICT';
    public const RELATION_CASCADE = 'CASCADE';
    public const RELATION_NULL = 'SET NULL';
    public const RELATION_NO_ACTION = 'NO ACTION';

    public const TYPE_VARCHAR = 'VARCHAR';
    public const TYPE_LONGTEXT = 'LONGTEXT';
    public const TYPE_TEXT = 'TEXT';
    public const TYPE_MEDIUMTEXT = 'MEDIUMTEXT';
    public const TYPE_TINYTEXT = 'TINYTEXT';
    public const TYPE_INT = 'INT';
    public const TYPE_TINYINT = 'TINYINT';
    public const TYPE_SMALLINT = 'SMALLINT';
    public const TYPE_MEDIUMINT = 'MEDIUMINT';
    public const TYPE_BIGINT = 'BIGINT';
    public const TYPE_DECIMAL = 'DECIMAL';
    public const TYPE_FLOAT = 'FLOAT';
    public const TYPE_DOUBLE = 'DOUBLE';
    public const TYPE_REAL = 'REAL';
    public const TYPE_BIT = 'BIT';
    public const TYPE_BOOLEAN = 'BOOLEAN';
    public const TYPE_SERIAL = 'SERIAL';
    public const TYPE_DATE = 'DATE';
    public const TYPE_DATETIME = 'DATETIME';
    public const TYPE_TIMESTAMP = 'TIMESTAMP';
    public const TYPE_TIME = 'TIME';
    public const TYPE_YEAR = 'YEAR';
    public const TYPE_CHAR = 'CHAR';
    public const TYPE_BINARY = 'BINARY';
    public const TYPE_VARBINARY = 'VARBINARY';
    public const TYPE_TINYBLOB = 'TINYBLOB';
    public const TYPE_MEDIUMBLOB = 'MEDIUMBLOB';
    public const TYPE_BLOB = 'BLOB';
    public const TYPE_LONGBLOB = 'LONGBLOB';
    public const TYPE_ENUM = 'ENUM';
    public const TYPE_SET = 'SET';
    public const TYPE_GEOMETRY = 'GEOMETRY';
    public const TYPE_POINT = 'POINT';
    public const TYPE_LINESTRING = 'LINESTRING';
    public const TYPE_POLYGON = 'POLYGON';
    public const TYPE_MULTIPOINT = 'MULTIPOINT';
    public const TYPE_MULTILINESTRING = 'MULTILINESTRING';
    public const TYPE_MULTIPOLYGON = 'MULTIPOLYGON';
    public const TYPE_GEOMETRYCOLLECTION = 'GEOMETRYCOLLECTION';

    public static array $TYPES = [
        self::TYPE_VARCHAR,
        self::TYPE_LONGTEXT,
        self::TYPE_TEXT,
        self::TYPE_MEDIUMTEXT,
        self::TYPE_TINYTEXT,
        self::TYPE_INT,
        self::TYPE_TINYINT,
        self::TYPE_SMALLINT,
        self::TYPE_MEDIUMINT,
        self::TYPE_BIGINT,
        self::TYPE_DECIMAL,
        self::TYPE_FLOAT,
        self::TYPE_DOUBLE,
        self::TYPE_REAL,
        self::TYPE_BIT,
        self::TYPE_BOOLEAN,
        self::TYPE_SERIAL,
        self::TYPE_DATE,
        self::TYPE_DATETIME,
        self::TYPE_TIMESTAMP,
        self::TYPE_TIME,
        self::TYPE_YEAR,
        self::TYPE_CHAR,
        self::TYPE_BINARY,
        self::TYPE_VARBINARY,
        self::TYPE_TINYBLOB,
        self::TYPE_MEDIUMBLOB,
        self::TYPE_BLOB,
        self::TYPE_LONGBLOB,
        self::TYPE_ENUM,
        self::TYPE_SET,
        self::TYPE_GEOMETRY,
        self::TYPE_POINT,
        self::TYPE_LINESTRING,
        self::TYPE_POLYGON,
        self::TYPE_MULTIPOINT,
        self::TYPE_MULTILINESTRING,
        self::TYPE_MULTIPOLYGON,
        self::TYPE_GEOMETRYCOLLECTION,
    ];

    public static array $RELATION_TYPES = [
        self::RELATION_CASCADE,
        self::RELATION_NO_ACTION,
        self::RELATION_RESTRICT,
        self::RELATION_NULL,
    ];

    protected Table $table;
    protected ?string $name = null;
    protected ?string $type = null;
    protected ?int $length = null;
    protected ?string $defaultValue = null;
    protected ?string $encoding = null;
    protected ?string $attributes = null;
    protected bool $nullable = false;
    protected bool $increment = false;
    protected ?string $comment = null;
    protected bool $drop = false;
    protected bool $change = false;
    protected ?string $after = null;
    protected bool $removeRelation = false;
    protected ?string $relationTable = null;
    protected ?string $relationColumn = null;
    protected ?string $relationUpdateType = null;
    protected ?string $relationDeleteType = null;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function primary(): self
    {
        return $this->setIndex(Index::TYPE_PRIMARY);
    }

    public function increment(): self
    {
        return $this->primary()->setIncrement(true);
    }

    public function index(?int $length = null): self
    {
        return $this->setIndex(Index::TYPE_INDEX, $length);
    }

    public function fullText(): self
    {
        return $this->setIndex(Index::TYPE_FULLTEXT);
    }

    public function default(string $defaultValue): self
    {
        return $this->setDefaultValue($defaultValue);
    }

    public function nullable(): self
    {
        return $this->setNullable(true);
    }

    public function string(int $length = 255): self
    {
        return $this->setType(static::TYPE_VARCHAR)->setLength($length);
    }

    public function integer(?int $length = null): self
    {
        return $this->setType(static::TYPE_INT)->setLength($length);
    }

    public function bigint(): self
    {
        return $this->setType(static::TYPE_BIGINT);
    }

    public function bool(): self
    {
        return $this
            ->setType(static::TYPE_TINYINT)
            ->setDefaultValue(0)
            ->setLength(1);
    }

    public function text(): self
    {
        return $this->setType(static::TYPE_TEXT);
    }

    public function longtext(): self
    {
        return $this->setType(static::TYPE_LONGTEXT);
    }

    public function datetime(): self
    {
        return $this->setType(static::TYPE_DATETIME);
    }

    public function date(): self
    {
        return $this->setType(static::TYPE_DATE);
    }

    public function blob(): self
    {
        return $this->setType(static::TYPE_LONGBLOB);
    }

    public function float(): self
    {
        return $this->setType(static::TYPE_FLOAT);
    }

    public function double(): self
    {
        return $this->setType(static::TYPE_DOUBLE);
    }

    public function decimal(): self
    {
        return $this->setType(static::TYPE_DECIMAL);
    }

    public function timestamp(): self
    {
        return $this->setType(static::TYPE_TIMESTAMP);
    }

    public function time(): self
    {
        return $this->setType(static::TYPE_TIME);
    }

    /**
     * @param string $table
     * @param string $column
     * @param string $delete
     * @param string $update
     * @return static
     * @throws InvalidArgumentException
     */
    public function relation(string $table, string $column, string $delete = self::RELATION_CASCADE, string $update = self::RELATION_RESTRICT): self
    {
        if (in_array($delete, static::$RELATION_TYPES, true) === false) {
            throw new InvalidArgumentException('Unknown relation type for delete. Valid types are: ' . implode(', ', static::$RELATION_TYPES));
        }

        if (in_array($update, static::$RELATION_TYPES, true) === false) {
            throw new InvalidArgumentException('Unknown relation type for delete. Valid types are: ' . implode(', ', static::$RELATION_TYPES));
        }

        $this->relationTable = $table;
        $this->relationColumn = $column;
        $this->relationUpdateType = $update;
        $this->relationDeleteType = $delete;

        return $this;
    }

    /**
     * Remove relation
     *
     * @param string $table
     * @param string $column
     * @return static
     */
    public function removeRelation(string $table, string $column): self
    {
        $this->removeRelation = true;
        $this->relationTable = $table;
        $this->relationColumn = $column;

        return $this;
    }

    public function drop(): self
    {
        $this->drop = true;

        return $this;
    }

    public function getDrop(): bool
    {
        return $this->drop;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setLength(?int $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setDefaultValue(string $value): self
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setEncoding(string $encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    public function setAttributes(string $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getAttributes(): ?string
    {
        return $this->attributes;
    }

    public function setNullable(bool $bool): self
    {
        $this->nullable = $bool;

        return $this;
    }

    public function getNullable(): ?bool
    {
        return (bool)$this->nullable;
    }

    public function setIndex(string $type, ?int $length = null): self
    {
        $this->table->addIndex(
            (new Index($this->table, $type))->addColumn($this->getName(), $length)
        );

        return $this;
    }

    public function getIndex(): ?Index
    {
        return $this->table->getIndex($this->getName());
    }

    public function setIndexLength(?int $length): self
    {
        $index = $this->getIndex();

        if ($index !== null) {
            $index->setColumnLength($this->getName(), $length);
        }

        return $this;
    }

    public function getIndexLength(): ?int
    {
        $index = $this->getIndex();

        if ($index !== null) {
            return $index->getColumnLength($this->getName());
        }

        return null;
    }

    public function setIncrement(bool $increment): self
    {
        $this->increment = $increment;

        $this->primary();

        return $this;
    }

    public function getIncrement(): bool
    {
        return $this->increment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function after(string $column): self
    {
        $this->after = $column;

        return $this;
    }

    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * Get foreign-key
     * @return string
     */
    public function getRelationKey(): string
    {
        return sprintf('%s_%s_fk', $this->table->getName(), $this->getName());
    }

    public function getRemoveRelation(): bool
    {
        return $this->removeRelation;
    }

    public function getRelationTable(): ?string
    {
        return $this->relationTable;
    }

    public function getRelationColumn(): ?string
    {
        return $this->relationColumn;
    }

    public function getRelationUpdateType(): ?string
    {
        return $this->relationUpdateType;
    }

    public function getRelationDeleteType(): ?string
    {
        return $this->relationDeleteType;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function exists(): bool
    {
        return ($this->table->getConnection()->value("SHOW COLUMNS FROM `{$this->table->getName()}` LIKE ?", [$this->getName()]) !== false);
    }

    /**
     * Generates column query
     *
     * @param string $type
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getQuery(string $type): ?string
    {
        if ($this->getDrop() === true) {
            if ($this->exists() === true) {
                $this->table->getConnection()->nonQuery(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $this->table->getName(), $this->getName()));
            }

            return null;
        }

        $query = '';
        $alterColumn = '';
        $modify = false;
        $columnType = $this->getType();

        if ($columnType !== null) {

            if ($type === Table::TYPE_ALTER) {

                $alterColumn = 'ADD COLUMN';

                if ($this->exists() === true) {
                    $alterColumn = 'MODIFY';
                    $modify = true;
                }
            }

            $query .= sprintf('%s `%s` %s%s %s%s%s%s%s%s, ',
                $alterColumn,
                $this->getName(),
                $columnType,
                ($this->getLength() ? " ({$this->getLength()})" : ''),
                $this->getAttributes(),
                ($this->getNullable() === false ? ' NOT null' : ' null'),
                ($this->getDefaultValue() !== null) ? PdoHelper::formatQuery(' DEFAULT %s', [$this->getDefaultValue()]) : '',
                ($this->getComment() !== null) ? PdoHelper::formatQuery(' COMMENT %s', [$this->getComment()]) : '',
                ($this->getAfter() !== null) ? " AFTER `{$this->getAfter()}`" : '',
                ($this->getIncrement() === true ? ' AUTO_INCREMENT' : '')
            );
        }

        if ($this->getIndex() !== null) {
            if ($modify === true) {
                $index = $this->getIndex();
                if ($index !== null) {
                    $index->drop();
                }
            }

            $query .= sprintf('%1$s %2$s `%3$s`(`%3$s`%4$s), ',
                (($type === Table::TYPE_ALTER) ? 'ADD' : ''),
                $this->getIndex()->getType(),
                $this->getIndex()->getName(),
                ($this->getIndexLength() !== null) ? " ({$this->getIndexLength()})" : '',
            );
        }

        if ($this->getRelationTable() !== null && $this->getRelationColumn() !== null) {

            if ($this->getRemoveRelation() === true) {

                if ($type !== Table::TYPE_ALTER) {
                    throw new InvalidArgumentException('You cannot remove a relation when creating a new table.');
                }

                $query .= sprintf('DROP FOREIGN KEY `%s`, ', $this->getRelationKey());

            } else {

                if ($this->table->foreignExist($this->getRelationKey()) === true) {
                    $this->table->dropForeign([
                        $this->getRelationKey(),
                    ]);
                }

                $query .= sprintf('%1$s CONSTRAINT `%2$s` FOREIGN KEY (`%3$s`) REFERENCES `%4$s`(`%5$s`) ON DELETE %6$s ON UPDATE %7$s, ',
                    ($type === Table::TYPE_ALTER) ? 'ADD' : '',
                    $this->getRelationKey(),
                    $this->getName(),
                    $this->getRelationTable(),
                    $this->getRelationColumn(),
                    $this->getRelationDeleteType(),
                    $this->getRelationUpdateType());

            }

        }

        return trim(trim($query, ', '));
    }

}