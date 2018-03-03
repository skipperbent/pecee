<?php
namespace Pecee\DB\Schema;

class Column
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var int|null
     */
    protected $length;

    /**
     * @var string|null
     */
    protected $defaultValue;

    /**
     * @var string|null
     */
    protected $encoding;

    /**
     * @var string|null
     */
    protected $attributes;

    /**
     * @var bool|null
     */
    protected $nullable;

    /**
     * @var string|null
     */
    protected $index;

    /**
     * @var bool|null
     */
    protected $increment;

    /**
     * @var string|null
     */
    protected $comment;

    /**
     * @var bool
     */
    protected $drop = false;

    /**
     * @var bool
     */
    protected $change = false;

    /**
     * @var string|null
     */
    protected $after;

    /**
     * @var string|null
     */
    protected $relationTable;

    /**
     * @var string|null
     */
    protected $relationColumn;

    /**
     * @var string|null
     */
    protected $relationUpdateType;

    /**
     * @var string|null
     */
    protected $relationDeleteType;

    public const INDEX_PRIMARY = 'PRIMARY KEY';
    public const INDEX_UNIQUE = 'UNIQUE';
    public const INDEX_INDEX = 'INDEX';
    public const INDEX_FULLTEXT = 'FULLTEXT';

    public const RELATION_TYPE_RESTRICT = 'RESTRICT';
    public const RELATION_TYPE_CASCADE = 'CASCADE';
    public const RELATION_TYPE_SET_NULL = 'SET NULL';
    public const RELATION_TYPE_NO_ACTION = 'NO ACTION';

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

    public static $INDEXES = [
        self::INDEX_PRIMARY,
        self::INDEX_UNIQUE,
        self::INDEX_INDEX,
        self::INDEX_FULLTEXT,
    ];

    public static $TYPES = [
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

    public static $RELATION_TYPES = [
        self::RELATION_TYPE_CASCADE,
        self::RELATION_TYPE_NO_ACTION,
        self::RELATION_TYPE_RESTRICT,
        self::RELATION_TYPE_SET_NULL,
    ];

    // Default values

    public function __construct($table)
    {
        $this->table = $table;
        $this->change = false;
    }

    public function primary()
    {
        $this->setIndex(static::INDEX_PRIMARY);

        return $this;
    }

    /**
     * @return static $this
     */
    public function increment()
    {
        $this->primary()->setIncrement(true);

        return $this;
    }

    public function index()
    {
        $this->setIndex(static::INDEX_INDEX);

        return $this;
    }

    public function nullable()
    {
        $this->setNullable(true);

        return $this;
    }

    public function string($length = 255)
    {
        $this->setType(static::TYPE_VARCHAR);
        $this->setLength($length);

        return $this;
    }

    public function integer($lenght = null)
    {
        $this->setType(static::TYPE_INT);
        $this->setLength($lenght);

        return $this;
    }

    public function bigint()
    {
        $this->setType(static::TYPE_BIGINT);

        return $this;
    }

    public function bool()
    {
        $this->setType(static::TYPE_TINYINT);
        $this->setNullable(true);
        $this->setLength(1);

        return $this;
    }

    public function text()
    {
        $this->setType(static::TYPE_TEXT);

        return $this;
    }

    public function longtext()
    {
        $this->setType(static::TYPE_LONGTEXT);

        return $this;
    }

    public function datetime()
    {
        $this->setType(static::TYPE_DATETIME);

        return $this;
    }

    public function date()
    {
        $this->setType(static::TYPE_DATE);

        return $this;
    }

    public function blob()
    {
        $this->setType(static::TYPE_LONGBLOB);

        return $this;
    }

    public function float()
    {
        $this->setType(static::TYPE_FLOAT);

        return $this;
    }

    public function double()
    {
        $this->setType(static::TYPE_DOUBLE);

        return $this;
    }

    public function decimal()
    {
        $this->setType(static::TYPE_DECIMAL);

        return $this;
    }

    public function timestamp()
    {
        $this->setType(static::TYPE_TIMESTAMP);

        return $this;
    }

    public function time()
    {
        $this->setType(static::TYPE_TIME);

        return $this;
    }

    /**
     * @param string $table
     * @param string $column
     * @param string $delete
     * @param string $update
     * @return static
     * @throws \InvalidArgumentException
     */
    public function relation($table, $column, $delete = self::RELATION_TYPE_CASCADE, $update = self::RELATION_TYPE_RESTRICT)
    {

        if (\in_array($delete, static::$RELATION_TYPES, true) === false) {
            throw new \InvalidArgumentException('Unknown relation type for delete. Valid types are: ' . implode(', ', static::$RELATION_TYPES));
        }

        if (\in_array($update, static::$RELATION_TYPES, true) === false) {
            throw new \InvalidArgumentException('Unknown relation type for delete. Valid types are: ' . implode(', ', static::$RELATION_TYPES));
        }

        $this->relationTable = $table;
        $this->relationColumn = $column;
        $this->relationUpdateType = $update;
        $this->relationDeleteType = $delete;

        return $this;
    }

    public function drop()
    {
        $this->drop = true;

        return $this;
    }

    public function getDrop()
    {
        return $this->drop;
    }

    public function change()
    {
        $this->change = true;

        return $this;
    }

    public function getChange()
    {
        return $this->change;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setNullable($bool)
    {
        $this->nullable = $bool;

        return $this;
    }

    public function getNullable()
    {
        return $this->nullable;
    }

    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setIncrement($increment)
    {
        $this->increment = $increment;

        $this->primary();

        return $this;
    }

    public function getIncrement()
    {
        return $this->increment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function after($column)
    {
        $this->after = $column;

        return $this;
    }

    public function getAfter()
    {
        return $this->after;
    }

    public function getRelationTable()
    {
        return $this->relationTable;
    }

    public function getRelationColumn()
    {
        return $this->relationColumn;
    }

    public function getRelationUpdateType()
    {
        return $this->relationUpdateType;
    }

    public function getRelationDeleteType()
    {
        return $this->relationDeleteType;
    }

}