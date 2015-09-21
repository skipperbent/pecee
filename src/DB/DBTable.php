<?php
namespace Pecee\DB;
class DBTable {

    /**
     * @var array
     */
    protected $columns;
    protected $name;

    public function __construct($name = null) {
        $this->name = $name;
    }

    /**
     * @param $name
     * @param int|null $length
     * @return DBColumn
     */
    public function column($name) {
        $column = new DBColumn();
        $column->setName($name);

        $this->columns[] = $column;
        return $column;
    }

    public function getPrimary($default = null) {
        if(count($this->columns) > 0) {
            /* @var $column DBColumn */
            foreach($this->columns as $column) {
                if($column->getIndex() == DBColumn::INDEX_PRIMARY) {
                    return $column;
                }
            }
        }

        return $default;
    }

    public function getColumnByIndex($index) {
        return $this->columns[$index];
    }

    public function getColumnNames($lower = false) {
        $names = array();
        /* @var $column DBColumn */
        foreach($this->columns as $column) {
            if($lower) {
                $names[] = strtolower($column->getName());
            } else {
                $names[] = $column->getName();
            }
        }
        return $names;
    }

    public function getColumn($name, $strict = false) {
        /* @var $column DBColumn */
        foreach($this->columns as $column) {
            if(!$strict && strtolower($column->getName()) == strtolower($name) || $strict && $column->getName() == $name) {
                return $column;
            }
        }
        return null;
    }

    public function getColumns() {
        return $this->columns;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    /**
     * Create table
     */
    public function create() {
        $keys = array();
        $query = array();
        /* @var $column DBColumn */
        foreach($this->columns as $column) {
            $length = '';
            if($column->getLength()) {
                $length = '('.$column->getLength().')';
            }

            $tmp = sprintf('`%s` %s%s %s ', $column->getName(), $column->getType(), $length, $column->getAttributes());

            $tmp .= (!$column->getNullable()) ? 'NOT null' : 'null';

            if($column->getDefaultValue()) {
                $tmp .= DB::FormatQuery(' DEFAULT %s', array($column->getDefaultValue()));;
            }

            if($column->getComment()) {
                $tmp .= DB::FormatQuery(' COMMENT %s', array($column->getComment()));
            }

            if($column->getIncrement()) {
                $tmp .= ' AUTO_INCREMENT';
            }

            $query[] = $tmp;

            if($column->getIndex()) {
                $keys[] = sprintf('%s (`%s`)', $column->getIndex(), $column->getName());
            }
        }

        $query = array_merge($query,$keys);
        $sql = sprintf('CREATE TABLE `'. $this->name .'` (%s) ENGINE = InnoDB;', join(', ', $query));

        \Pecee\DB\DB::getInstance()->nonQuery($sql);
    }

}