<?php
namespace Pecee\Model;

use Pecee\Collection\CollectionItem;
use Pecee\DB\Pdo;
use Pecee\DB\PdoHelper;
use Pecee\Integer;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Str;

/**
 * Class LegacyModel
 * @package Pecee\Model
 * @deprecated This class is deprecated and will be removed in later releases. Please use Model class instead.
 */
abstract class LegacyModel implements \IteratorAggregate {

    protected $table;
    protected $results;

    protected $primary = 'id';
    protected $hidden = [];
    protected $with = [];
    protected $rename = [];
    protected $join = [];
    protected $columns = [];

    public function __construct() {
        // Set table name if its not already defined
        if($this->table === null) {
            $name = explode('\\', get_called_class());
            $name = str_ireplace('model', '', end($name));
            $this->table = strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $name));
        }

        $this->results = array('data' => array());
    }

    /**
     * Save item
     * @see \Pecee\Model\Model::save()
     * @return static
     * @throws ModelException
     */
    public function save() {
        if(!is_array($this->columns)) {
            throw new ModelException('Columns property not defined.');
        }

        $keys = array();
        $values = array();
        $concat = array();

        foreach($this->columns as $column) {
            $keys[] = $column;
            $values[] = $this->{$column};
            $concat[] = '?';
        }

        $sql = sprintf('INSERT INTO `%s`(%s) VALUES (%s);', $this->table, PdoHelper::joinArray($keys, true), join(',', $concat));

        $this->{$this->primary} = Pdo::getInstance()->insert($sql, $values);
        return $this;
    }

    public function delete() {
        if(!is_array($this->columns)) {
            throw new ModelException('Columns property not defined.');
        }

        $sql = sprintf('DELETE FROM `%s` WHERE `%s` = ?;', $this->table, $this->primary);
        Pdo::getInstance()->nonQuery($sql, [ $this->{$this->primary} ]);
    }

    public function exists() {

        if($this->{$this->primary} === null) {
            return false;
        }

        $sql = sprintf('SELECT `%1$s` FROM `%2$s` WHERE `%1$s` = ?;', $this->primary, $this->table);
        return Pdo::getInstance()->value($sql, [ $this->{$this->primary} ]);
    }

    /**
     * @return null|Model
     * @throws ModelException
     */
    public function update() {
        if(!is_array($this->columns)) {
            throw new ModelException('Columns property not defined.');
        }

        $concat = array();
        $values = array();

        foreach($this->columns as $name) {
            $value = $this->{$name};

            if(isset($this->results['data']['original'][$name]) && $this->results['data']['original'][$name] == $value) {
                continue;
            }

            $values[] = $value;
            $concat[] = PdoHelper::formatQuery('`'.$name.'` = ?', array($value));
        }

        if(count($concat) === 0) {
            return null;
        }

        $values[] = $this->{$this->primary};

        $sql = sprintf('UPDATE `%s` SET %s WHERE `%s` = ? LIMIT 1;', $this->table, join(', ', $concat), $this->primary);
        Pdo::getInstance()->nonQuery($sql, $values);
        return $this;
    }

    protected function getCountSql($sql) {
        $sql = (strripos($sql, 'LIMIT') <= 1 && strripos($sql, 'LIMIT') > -1) ? substr($sql, 0, strripos($sql, 'LIMIT')) : $sql;
        $sql = (strripos($sql, 'OFFSET') <= 1 && strripos($sql, 'OFFSET') > -1) ? substr($sql, 0, strripos($sql, 'OFFSET')) : $sql;

        return sprintf('SELECT COUNT(`' . $this->primary . '`) AS `Total` FROM (%s) AS `CountedResult`', $sql);
    }

    public static function queryCollection($query, $rows = null, $page = null, $args = null) {
        /* $var $model Model */
        $model = new static();
        $results = array();
        $query = str_ireplace('{table}', '`' . $model->getTable() . '`', $query);
        $args = ($args === null || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 3));
        $page = ($page === null) ? 0 : $page;

        if ($page !== null && $rows !== null && stripos($query, 'limit') === false) {
            $query .= sprintf(' LIMIT %s, %s', ($page * $rows), $rows);
        }

        $sql = PdoHelper::formatQuery($query, $args);
        $query =  Pdo::getInstance()->query($sql);

        $results['data']['max_fields'] = $query->columnCount();
        $results['data']['max_rows'] = $query->rowCount();
        $results['query'] = array($sql);
        $results['data']['rows'] = array();
        if($results['data']['max_rows'] > 0) {
            foreach($query->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $obj = static::onCreateModel(new CollectionItem($row));
                $obj->setRows($row);
                $results['data']['rows'][] = $obj;
            }
        }

        if($rows !== null && $page !== null) {
            $countSql = $model->getCountSql(PdoHelper::formatQuery($sql, $args));
            $results['query'][] = $countSql;
            $maxRows = Pdo::getInstance()->value($countSql);
            $results['data']['page'] = $page;
            $results['data']['rows_per_page'] = $rows;
            $results['data']['max_rows'] = intval($maxRows);
        }

        $model->setResults($results);
        return $model;
    }

    public static function query($query, $args = null) {
        /* $var $model Model */
        $model = new static();
        $results = array();
        $query = str_ireplace('{table}', '`' . $model->getTable() . '`', $query);
        $args = ($args === null || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 3));
        $sql = PdoHelper::formatQuery($query, $args);
        $query =  Pdo::getInstance()->query($sql);

        if($query !== null) {
            $results['data']['max_fields'] = $query->columnCount();
            $results['data']['max_rows'] = $query->rowCount();
            $results['query'] = $sql;
            $results['data']['rows'] = array();
            if($results['data']['max_rows'] > 0) {
                foreach($query->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $obj = static::onCreateModel(new CollectionItem($row));
                    $obj->setRows($row);
                    $results['data']['rows'][] = $obj;
                }
            }
            $model->setResults($results);
        }
        return $model;
    }

    /**
     * Returns model instance
     * @param CollectionItem $row
     * @return static
     */
    protected static function onCreateModel(CollectionItem $row) {
        return new static();
    }

    /**
     * Fetch all
     * @param string $query
     * @param null|string $args
     * @return static
     */
    public static function fetchAll($query, $args = null) {
        $args = ($args === null || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 1));
        return static::queryCollection($query, null, null, $args);
    }

    /**
     * Fetch all page
     * @param string $query
     * @param null|int $skip
     * @param null|int $rows
     * @param null|string $args
     * @return static
     */
    public static function fetchAllPage($query, $skip = null, $rows = null, $args=null) {
        $args = ($args === null || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 3));
        $skip = ($skip === null) ? 0 : $skip;
        if($skip !== null && $rows !== null) {
            $query = $query.' LIMIT ' . $skip . ',' . $rows;
        }
        $model = static::queryCollection($query, null, null, $args);
        $results = $model->getResults();
        $results['data']['rows_per_page'] = $rows;
        $results['data']['has_previous'] = ($skip > 0);
        $model->setResults($results);
        return $model;
    }

    /**
     * Fetch page
     * @param $query
     * @param null|int $rows
     * @param null|int $page
     * @param null|string $args
     * @return static
     */
    public static function fetchPage($query, $rows = null, $page = null, $args=null) {
        $args = ($args === null || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 3));
        return static::queryCollection($query, $rows, $page, $args);
    }

    /**
     * Fetch one
     * @param string $query
     * @param null|string $args
     * @return static
     */
    public static function fetchOne($query, $args=null) {
        $args = ($args === null || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 1));
        $model =  static::query($query . ((stripos($query, 'LIMIT') > 0) ? '' : ' LIMIT 1'), $args);
        if($model->hasRows()){
            return $model->getRow(0);
        }

        return $model;
    }

    public static function nonQuery($query, $args = null) {
        $args = ($args === null || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 1));

        $model = new static();
        $query = str_ireplace('{table}', '`' . $model->getTable() . '`', $query);

        Pdo::getInstance()->nonQuery(PdoHelper::formatQuery($query, $args));
    }

    public static function scalar($query, $args = null) {
        $args = ($args === null || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 1));

        $model = new static();
        $query = str_ireplace('{table}', '`' . $model->getTable() . '`', $query);

        return Pdo::getInstance()->value(PdoHelper::formatQuery($query, $args));
    }

    public function isCollection() {
        return (array_key_exists('max_rows', $this->results['data']));
    }

    protected function parseArrayData($data) {
        // If it's an array of Model instances, we get JSON output here

        if(is_array($data)) {
            $out = array();
            foreach($data as $d) {
                $out[] = $this->parseArrayData($d);
            }
            return $out;
        }

        $data = (!is_array($data) && !mb_detect_encoding($data, 'UTF-8', true)) ? utf8_encode($data) : $data;
        return (Integer::isInteger($data)) ? intval($data) : $data;
    }

    public function parseArrayRow($row) {
        return $row;
    }

    public function getArray(){

        if(!$this->isCollection()) {
            if(!$this->hasRow()) {
                return null;
            }
        }

        $arr = array('rows' => null);
        $arr = array_merge($arr, $this->results['data']);
        $rows = $this->results['data']['rows'];
        if($rows && is_array($rows)) {
            foreach($rows as $key=>$row){

                $key = (isset($this->rename[$key])) ? $this->rename[$key] : $key;

                if($row instanceof self) {
                    $rows[$key] = $row->getArray();
                } else {

                    if(in_array($key, $this->hidden)) {
                        unset($rows[$key]);
                        continue;
                    }

                    $rows[$key] = $this->parseArrayData($row);
                }
            }

            if($this->isCollection()) {
                $arr['has_next'] = $this->hasNext();
                $arr['has_previous'] = $this->hasPrevious();
            }
        }

        if(count($this->getResults()) === 1) {
            foreach($this->with as $with) {

                $method = Str::camelize($with);

                if(!method_exists($this, $method)) {
                    throw new ModelException('Missing required method ' . $method);
                }

                $output = call_user_func([$this, $method]);

                $with = (isset($this->rename[$with])) ? $this->rename[$with] : $with;

                if($output instanceof Model) {
                    $rows[$with] = $output->getArray();
                } else {
                    $rows[$with] = $output;
                }
            }
            return $this->parseArrayRow($rows);
        }

        $arr['rows'] = $rows;
        return $arr;
    }

    public function hasRows() {
        return (isset($this->results['data']['rows']) && count($this->results['data']['rows']) > 0);
    }

    public function hasRow() {
        return (bool)(count($this->results['data']['rows']));
    }

    /**
     * Get row
     * @param int $index
     * @return static
     */
    public function getRow($index) {
        return ($this->hasRows()) ? $this->results['data']['rows'][$index] : null;
    }

    public function setRow($key, $value) {
        $this->results['data']['rows'][$key] = $value;
    }

    public function setRows(array $rows) {
        if(!isset($this->results['data']['max_rows'])) {
            $this->results['data']['max_rows'] = count($rows);
        }
        $this->results['data']['rows'] = $rows;
        $this->results['data']['original'] = $rows;

        if(count($this->join)) {
            foreach($this->join as $join) {
                $method = Str::camelize($join);
                $this->{$join} = $this->$method();
            }
        }
    }

    /**
     * Get rows
     * @return array|null
     */
    public function getRows() {
        return ($this->hasRows()) ? $this->results['data']['rows'] : null;
    }

    public function getMaxRows() {
        return isset($this->results['data']['max_rows']) ? $this->results['data']['max_rows'] : 0;
    }

    public function setMaxRows($rows) {
        $this->results['data']['max_rows'] = $rows;
    }

    public function getMaxPages() {
        return ($this->getMaxRows() > 0 && $this->getRowsPerPage() > 0) ? ceil($this->getMaxRows()/$this->getRowsPerPage()) : 0;
    }

    public function setPage($page) {
        $this->results['data']['page'] = $page;
    }

    public function getRowsPerPage() {
        return isset($this->results['data']['rows_per_page']) ? $this->results['data']['rows_per_page'] : 0;
    }

    public function setRowsPerPage($rows) {
        $this->results['data']['rows_per_page'] = $rows;
    }

    public function getPage() {
        return isset($this->results['data']['page']) ? $this->results['data']['page'] : 0;
    }

    public function setResults($results) {
        $this->results = $results;
    }

    public function getResults() {
        return $this->results;
    }

    public function hasNext() {
        return ($this->getPage() + 1 < $this->getMaxPages());
    }

    public function hasPrevious() {
        return ($this->getPage() > 0);
    }

    public function getTable() {
        return $this->table;
    }

    public function __get($name) {
        return (isset($this->results['data']['rows'][$name])) ? $this->results['data']['rows'][$name] : null;
    }

    public function __set($name, $value) {
        $this->results['data']['rows'][strtolower($name)] = $value;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator() {
        return new \ArrayIterator(($this->hasRows()) ? $this->getRows() : array());
    }

    public function getPrimary() {
        return $this->primary;
    }

    public static function getById($id) {
        $static = new static();
        return static::fetchOne('SELECT * FROM {table} WHERE `'. $static->getPrimary() .'` = %s', $id);
    }

}