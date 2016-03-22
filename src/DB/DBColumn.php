<?php
namespace Pecee\DB;

class DBColumn {
    
    protected $name;
    protected $type;
    protected $length;
    protected $defaultValue;
    protected $encoding;
    protected $attributes;
    protected $nullable;
    protected $index;
    protected $increment;
    protected $comment;
    protected $relationTable;
    protected $relationColumn;

    const INDEX_PRIMARY = 'PRIMARY KEY';
    const INDEX_UNIQUE = 'UNIQUE';
    const INDEX_INDEX = 'INDEX';
    const INDEX_FULLTEXT = 'FULLTEXT';

    const TYPE_VARCHAR = 'VARCHAR';
    const TYPE_LONGTEXT = 'LONGTEXT';
    const TYPE_TEXT = 'TEXT';
    const TYPE_MEDIUMTEXT = 'MEDIUMTEXT';
    const TYPE_TINYTEXT = 'TINYTEXT';
    const TYPE_INT = 'INT';
    const TYPE_TINYINT = 'TINYINT';
    const TYPE_SMALLINT = 'SMALLINT';
    const TYPE_MEDIUMINT = 'MEDIUMINT';
    const TYPE_BIGINT = 'BIGINT';
    const TYPE_DECIMAL = 'DECIMAL';
    const TYPE_FLOAT = 'FLOAT';
    const TYPE_DOUBLE = 'DOUBLE';
    const TYPE_REAL = 'REAL';
    const TYPE_BIT = 'BIT';
    const TYPE_BOOLEAN = 'BOOLEAN';
    const TYPE_SERIAL = 'SERIAL';
    const TYPE_DATE = 'DATE';
    const TYPE_DATETIME = 'DATETIME';
    const TYPE_TIMESTAMP = 'TIMESTAMP';
    const TYPE_TIME = 'TIME';
    const TYPE_YEAR = 'YEAR';
    const TYPE_CHAR = 'CHAR';
    const TYPE_BINARY = 'BINARY';
    const TYPE_VARBINARY = 'VARBINARY';
    const TYPE_TINYBLOB = 'TINYBLOB';
    const TYPE_MEDIUMBLOB = 'MEDIUMBLOB';
    const TYPE_BLOB = 'BLOB';
    const TYPE_LONGBLOB = 'LONGBLOB';
    const TYPE_ENUM = 'ENUM';
    const TYPE_SET = 'SET';
    const TYPE_GEOMETRY = 'GEOMETRY';
    const TYPE_POINT = 'POINT';
    const TYPE_LINESTRING = 'LINESTRING';
    const TYPE_POLYGON = 'POLYGON';
    const TYPE_MULTIPOINT = 'MULTIPOINT';
    const TYPE_MULTILINESTRING = 'MULTILINESTRING';
    const TYPE_MULTIPOLYGON = 'MULTIPOLYGON';
    const TYPE_GEOMETRYCOLLECTION = 'GEOMETRYCOLLECTION';

    public static $INDEXES = [
        self::INDEX_PRIMARY,
        self::INDEX_UNIQUE,
        self::INDEX_INDEX,
        self::INDEX_FULLTEXT
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
        self::TYPE_GEOMETRYCOLLECTION
    ];

    // Default values

    public function __construct() {
        $this->relation = array();
    }

    public function primary() {
        $this->setIndex(self::INDEX_PRIMARY);
        return $this;
    }

    public function increment() {
        $this->setIncrement(true);
        return $this;
    }

    public function index() {
        $this->setIndex(self::INDEX_INDEX);
        return $this;
    }

    public function nullable() {
        $this->setNullable(true);
        return $this;
    }

    public function string($length = 255) {
        $this->setType(self::TYPE_VARCHAR);
        $this->setLength($length);
        return $this;
    }

    public function integer($lenght = null) {
        $this->setType(self::TYPE_INT);
        $this->setLength($lenght);
        return $this;
    }

    public function bigint() {
        $this->setType(self::TYPE_BIGINT);
        return $this;
    }

    public function bool() {
        $this->setType(self::TYPE_TINYINT);
        $this->setNullable(true);
        $this->setLength(1);
        return $this;
    }

    public function text(){
        $this->setType(self::TYPE_TEXT);
        return $this;
    }

    public function longtext() {
        $this->setType(self::TYPE_LONGTEXT);
        return $this;
    }

    public function datetime() {
        $this->setType(self::TYPE_DATETIME);
        return $this;
    }

    public function date() {
        $this->setType(self::TYPE_DATE);
        return $this;
    }

    public function blob() {
        $this->setType(self::TYPE_LONGBLOB);
        return $this;
    }

    public function float() {
        $this->setType(self::TYPE_FLOAT);
        return $this;
    }

    public function double() {
        $this->setType(self::TYPE_DOUBLE);
        return $this;
    }

    public function decimal() {
        $this->setType(self::TYPE_DECIMAL);
        return $this;
    }

    public function timestamp() {
        $this->setType(self::TYPE_TIMESTAMP);
        return $this;
    }

    public function time() {
        $this->setType(self::TYPE_TIME);
        return $this;
    }

    public function relation($table, $column) {
        $this->relationTable = $table;
        $this->relationColumn = $column;
        return $this;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    public function getType() {
        return $this->type;
    }

    public function setLength($length) {
        $this->length = $length;
        return $this;
    }

    public function getLength() {
        return $this->length;
    }

    public function setDefaultValue($value) {
        $this->defaultValue = $value;
        return $this;
    }

    public function getDefaultValue() {
        return $this->defaultValue;
    }

    public function setEncoding($encoding) {
        $this->encoding = $encoding;
        return $this;
    }

    public function getEncoding() {
        return $this->encoding;
    }

    public function setAttributes($attributes) {
        $this->attributes = $attributes;
        return $this;
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function setNullable($bool) {
        $this->nullable = $bool;
        return $this;
    }

    public function getNullable() {
        return $this->nullable;
    }

    public function setIndex($index) {
        $this->index = $index;
        return $this;
    }

    public function getIndex() {
        return $this->index;
    }

    public function setIncrement($increment) {
        $this->increment = $increment;
        return $this;
    }

    public function getIncrement() {
        return $this->increment;
    }

    public function setComment($comment) {
        $this->comment = $comment;
        return $this;
    }

    public function getComment() {
        return $this->comment;
    }

    public function getRelationTable() {
        return $this->relationTable;
    }

    public function getRelationColumn() {
        return $this->relationColumn;
    }

}