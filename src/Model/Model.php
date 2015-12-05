<?php
namespace Pecee\Model;

use Pecee\Collection\CollectionItem;
use \Pecee\DB\DBTable;
use Pecee\DB\Pdo;
use Pecee\DB\PdoHelper;
use Pecee\Integer;

abstract class Model implements IModel, \IteratorAggregate {

    protected $table;
    protected $columns;
    protected $query;
    protected $results;
    protected $autoCreate;

    public function __construct(DBTable $table) {
        $this->autoCreate = true;
        $this->table = $table;

        // Set table name if its not already defined
        if(is_null($this->table->getName())) {
            $name = explode('\\', get_called_class());
            $name = $name[count($name)-1];
            $name = str_ireplace('model', '', $name);
            $name = strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $name));
            $this->table->setName($name);
        }

        $defaultRows = array();
        if(count($this->table->getColumns()) > 0) {
            /* @var $column \Pecee\DB\DBColumn */
            foreach($this->table->getColumns() as $column) {
                $defaultRows[$column->getName()] = ($column->getNullable()) ? null : '';
            }
        }

        $this->results = array('data' => array('rows' => $defaultRows));
    }

    /**
     * Save item
     * @see Pecee\Model\Model::save()
     * @return static
     * @throws ModelException
     */
    public function save() {
        if(!$this->hasRows() || !is_array($this->getRows())) {
            throw new ModelException('Table rows missing from constructor.');
        }

        $keys = array();
        $values = array();

        foreach($this->getRows() as $name => $row) {
            $column = $this->table->getColumn($name);
            if ($column !== null) {
                $keys[] = $column->getName();
                $values[] = $row;
            }
        }

        $sql = sprintf('INSERT INTO `%s`(%s) VALUES (%s);', $this->table->getName(), PdoHelper::joinArray($keys, true), PdoHelper::joinArray($values));

        try {
            $id = Pdo::getInstance()->insert($sql);
        }catch(\PDOException $e) {
            if($e->getCode() == '42S02' && $this->getAutoCreateTable()) {
                $this->table->create();
                return $this->save();
            }
            throw $e;
        }
        $primary = $this->table->getPrimary($this->table->getColumnByIndex(0));
        if($primary) {
            $this->results['data']['rows'][$primary->getName()] = $id;
        }
        return $this;
    }

    public function delete() {
        if(!$this->hasRows() || !is_array($this->getRows())) {
            throw new ModelException('Table rows missing from constructor.');
        }

        $primaryKey = $this->table->getPrimary($this->table->getColumnByIndex(0));
        $primaryValue = array_values($this->getRows());
        $primaryValue = $primaryValue[0];
        if($primaryKey && $primaryValue) {
            $sql = sprintf('DELETE FROM `%s` WHERE `%s` = %s;', $this->table->getName(), $primaryKey->getName(), PdoHelper::formatQuery('%s', array($primaryValue)));
            Pdo::getInstance()->nonQuery($sql);
        }
    }

    public function exists() {
        $primaryKey = $this->table->getPrimary($this->table->getColumnByIndex(0));
        $primaryValue = array_values($this->getRows());
        $primaryValue = $primaryValue[0];
        if($primaryKey && $primaryValue) {
            $sql = sprintf('SELECT `%s` FROM `%s` WHERE `%s` = %s;', $primaryKey->getName(), $this->table->getName(), $primaryKey->getName(), PdoHelper::formatQuery('%s', array($primaryValue)));
            return Pdo::getInstance()->value($sql);
        }
        return false;
    }

    /**
     * @return null|Model
     * @throws ModelException
     */
    public function update() {
        if(!$this->hasRows() || !is_array($this->getRows())) {
            throw new ModelException('Table rows missing from constructor.');
        }
        $primaryKey = $this->table->getPrimary($this->table->getColumnByIndex(0));
        $primaryValue = array_values($this->getRows());
        $primaryValue = $primaryValue[0];

        if($primaryKey && $primaryValue) {
            $concat=array();
            foreach($this->table->getColumnNames(false, true) as $key=>$name) {
                $val = $this->getRow($name);
                $concat[] = PdoHelper::formatQuery('`'.$name.'` = %s', array($val));
            }
            $sql = sprintf('UPDATE `%s` SET %s WHERE `%s` = \'%s\' LIMIT 1;', $this->table->getName(), join(', ', $concat), $primaryKey->getName(), PdoHelper::escape($primaryValue));

            try {
                Pdo::getInstance()->nonQuery($sql);
            }catch(\PDOException $e) {
                if($e->getCode() == '42S02' && $this->getAutoCreateTable()) {
                    $this->table->create();
                    return $this->save();
                }
                throw $e;
            }
        }
        return null;
    }

    protected function getCountSql($sql) {
        $sql = (strripos($sql, 'LIMIT') <= 1 && strripos($sql, 'LIMIT') > -1) ? substr($sql, 0, strripos($sql, 'LIMIT')) : $sql;
        $sql = (strripos($sql, 'OFFSET') <= 1 && strripos($sql, 'OFFSET') > -1) ? substr($sql, 0, strripos($sql, 'OFFSET')) : $sql;

        $primary = $this->table->getPrimary($this->table->getColumnByIndex(0));
        return sprintf('SELECT COUNT(`'.$primary->getName().'`) AS `Total` FROM (%s) AS `CountedResult`', $sql);
    }

    public static function query($query, $rows = null, $page = null, $args = null) {
        /* $var $model Model */
        $model = static::onCreateModel();
        $results = array();
        $fetchPage = false;
        $countSql = null;
        $query = str_ireplace('{table}', '`' . $model->getTable()->getName() . '`', $query);
        $args = (is_null($args) || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 3));
        if(!is_null($rows)){
            $page = (is_null($page)) ? 0 : $page;
            $countSql = $model->getCountSql(PdoHelper::formatQuery($query, $args));
            if(stripos($query, 'limit') === false) {
                $query .= sprintf(' LIMIT %s, %s', ($page * $rows), $rows);
            }
            $fetchPage = true;
        }
        $sql = PdoHelper::formatQuery($query, $args);
        try {
            $query =  Pdo::getInstance()->query($sql);
        } catch(\PdoException $e) {
            if($e->getCode() == '42S02' && $model->getAutoCreateTable()) {
                $model->getTable()->create();
                return $model::query($sql, $rows, $page, $args);
            }
            throw $e;
        }
        if($query) {
            $results['data']['numFields'] = $query->columnCount();
            $results['data']['numRows'] = $query->rowCount();
            $results['query'][] = $sql;
            if($results['data']['numRows'] > 0) {
                foreach($query->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $obj = static::onCreateModel(new CollectionItem($row));
                    $obj->setRows($row);
                    $results['data']['rows'][]=$obj;
                }
            }
            if($fetchPage) {
                $results['query'][] = $countSql;
                $maxRows = Pdo::getInstance()->value($countSql);
                $results['data']['page'] = $page;
                $results['data']['rowsPerPage'] = $rows;
                $results['data']['maxRows'] = intval($maxRows);
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
    protected static function onCreateModel(CollectionItem $row = null) {
        return new static();
    }

    /**
     * Fetch all
     * @param string $query
     * @param null|string $args
     * @return static
     */
    public static function fetchAll($query, $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 1));
        return static::query($query, null, null, $args);
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
        $args = (is_null($args) || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 3));
        $skip = (is_null($skip)) ? 0 : $skip;
        $model = static::onCreateModel();
        try {
            $maxRows = Pdo::getInstance()->value($model->getCountSql(PdoHelper::formatQuery($query, $args)));
            if(!is_null($skip) && !is_null($rows)) {
                $query = $query.' LIMIT ' . $skip . ',' . $rows;
            }
            $model = static::query($query, null, null, $args);
        } catch(\PdoException $e) {
            if($e->getCode() == '42S02' && $model->getAutoCreateTable()) {
                $model->getTable()->create();
                return model::fetchAllPage($query, $skip, $rows, $args);
            }
            throw $e;
        }
        $results = $model->getResults();
        $results['data']['rowsPerPage'] = $rows;
        $results['data']['maxRows'] = intval($maxRows);
        $results['data']['hasNext'] = ($rows+$skip < intval($maxRows));
        $results['data']['hasPrevious'] = ($skip>0);
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
        $args = (is_null($args) || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 3));
        return static::query($query, $rows, $page, $args);
    }

    /**
     * Fetch one
     * @param string $query
     * @param null|string $args
     * @return static
     */
    public static function fetchOne($query, $args=null) {
        $args = (is_null($args) || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 1));
        $model =  static::query($query . ((stripos($query, 'LIMIT') > 0) ? '' : ' LIMIT 1'), null, null, $args);
        if($model->hasRows()){
            $results = $model->getResults();
            if(isset($results['data']['rows'])) {
                return $results['data']['rows'][0];
            }
        }

        return $model;
    }

    public static function nonQuery($query, $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 1));

        $model = static::onCreateModel();
        $query = str_ireplace('{table}', '`' . $model->getTable()->getName() . '`', $query);

        try {
            Pdo::getInstance()->nonQuery(PdoHelper::formatQuery($query, $args));
        } catch(\PdoException $e) {
            if($e->getCode() == '42S02' && $model->getAutoCreateTable()) {
                $model->getTable()->create();
                return $model::nonQuery($query, $args);
            }
            throw $e;
        }
    }

    public static function scalar($query, $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : PdoHelper::parseArgs(func_get_args(), 1));

        $model = static::onCreateModel();
        $query = str_ireplace('{table}', '`' . $model->getTable()->getName() . '`', $query);

        try {
            return Pdo::getInstance()->value(PdoHelper::formatQuery($query, $args));
        } catch(\PdoException $e) {
            if($e->getCode() == '42S02' && $model->getAutoCreateTable()) {
                $model->getTable()->create();
                return $model::scalar($query, $args);
            }
            throw $e;
        }
    }

    protected function parseJsonChild($data) {
        if($data instanceof self) {
            return $data->getAsJsonObject();
        }

        if(is_array($data)) {
            $out = array();
            foreach($data as $d) {
                $out[] = $this->parseJsonChild($d);
            }
            return $out;
        }
        return $data;
    }

    protected function parseJsonData($data) {
        // If it's an array of Model instances, we get JSON output here
        $data = $this->parseJsonChild($data);
        $data = (!is_array($data) && !mb_detect_encoding($data, 'UTF-8', true)) ? utf8_encode($data) : $data;
        return (Integer::isInteger($data)) ? intval($data) : $data;
    }

    public function getAsJsonObject(){
        $arr = array('rows' => null);
        $arr = array_merge($arr, (array)$this->results['data']);
        if($this->hasRows()){
            $rows = $this->results['data']['rows'];
            if($rows && is_array($rows)) {
                foreach($rows as $key=>$row){
                    if($row instanceof self) {
                        $rows[$key] = $row->getAsJsonObject();
                    } else {
                        $rows[$key] = $this->parseJsonData($row);
                    }
                }
            }
            if(count($this->getResults()) == 1) {
                return $rows;
            }
            $arr['rows']=$rows;
        }
        return $arr;
    }

    public function hasRows() {
        return (isset($this->results['data']['rows']) && count($this->results['data']['rows']) > 0);
    }

    public function hasRow() {
        return (isset($this->results['data']['rows']));
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
        $column = $this->table->getColumn($key);
        $name = ($column) ? $column->getName() : $key;
        $this->results['data']['rows'][$name] = $value;
    }

    public function setRows(array $rows) {
        if(!isset($this->results['data']['numRows'])) {
            $this->results['data']['numRows'] = count($rows);
        }
        $this->results['data']['rows'] = $rows;
    }

    /**
     * Get rows
     * @return array|null
     */
    public function getRows() {
        return ($this->hasRows()) ? $this->results['data']['rows'] : null;
    }

    public function getMaxRows() {
        return isset($this->results['data']['maxRows']) ? $this->results['data']['maxRows'] : 0;
    }

    public function setMaxRows($rows) {
        $this->results['data']['maxRows'] = $rows;
    }

    public function getMaxPages() {
        return ($this->getMaxRows() && $this->getNumRows()) ? ceil($this->getMaxRows()/$this->getNumRows()) : 0;
    }

    public function setPage($page) {
        $this->results['data']['page'] = $page;
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
        return ($this->getPage()+1 < $this->getMaxPages());
    }

    public function hasPrevious() {
        return ($this->getPage() > 0);
    }

    public function getTable() {
        return $this->table;
    }

    public function __get($name) {
        $name = ($this->table && $this->table->getColumn($name)) ? $this->table->getColumn($name)->getName() : strtolower($name);
        return (isset($this->results['data']['rows'][$name])) ? $this->results['data']['rows'][$name] : null;
    }

    public function __set($name, $value) {
        $this->results['data']['rows'][strtolower($name)] = $value;
    }

    public function setAutoCreateTable($bool) {
        $this->autoCreate = $bool;
    }

    public function getAutoCreateTable() {
        return $this->autoCreate;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator() {
        return new \ArrayIterator(($this->hasRows()) ? $this->getRows() : array());
    }

}