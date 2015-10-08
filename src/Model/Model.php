<?php
namespace Pecee\Model;
use \Pecee\DB\DB;
use \Pecee\DB\DBException;
use \Pecee\DB\DBTable;
use Pecee\PhpInteger;

abstract class Model implements IModel {
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
     * Counts fieldname in the database, giving the
     * number of rows in the table with the specified fieldname.
     *
     * @param string $fieldName
     * @param string $tableName
     * @param string $where
     * @param array|null $args
     * @throws DBException
     * @return int
     */
    public static function Count($fieldName, $tableName, $where = '', $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 3));
        try {
            return DB::getInstance()->count($fieldName, $tableName, $where, $args);
        } catch(DBException $e) {
            $class = static::OnCreateModel();
            if($e->getCode() == 1146 && $class->getAutoCreateTable()) {
                $class->getTable()->create();
                return $class::Count($fieldName, $tableName, $where, $args);
            }
            throw $e;
        }
    }

    /**
     * Returns maximum rows by given fieldname.
     *
     * @param string $fieldName
     * @param string $tableName
     * @param string $where
     * @param array|null $args
     * @throws DBException
     * @return int
     */
    public static function Max($fieldName, $tableName, $where = '', $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 3));
        try {
            return DB::getInstance()->max($fieldName, $tableName, $where, $args);
        } catch(DBException $e) {
            $class = static::OnCreateModel();
            if($e->getCode() == 1146 && $class->getAutoCreateTable()) {
                $class->getTable()->create();
                return $class::Max($fieldName, $tableName, $where, $args);
            }
            throw $e;
        }
    }

    /**
     * Save item
     * @see Pecee\Model\Model::save()
     * @return self
     * @throws ModelException
     */
    public function save() {
        if(!$this->hasRows() || !is_array($this->getRows())) {
            throw new ModelException('Table rows missing from constructor.');
        }
        $values = array_values($this->getRows());
        $sql = sprintf('INSERT INTO `%s`(%s) VALUES (%s);', $this->table->getName(), DB::JoinArray($this->table->getColumnNames(),true), DB::FormatQuery(DB::JoinValues($values, ', '), $values));
        $out = $this->Insert($sql,null);
        $primary = $this->table->getPrimary($this->table->getColumnByIndex(0));
        if($primary) {
            $this->results['data']['rows'][$primary->getName()] = $out->getInsertId();
        }
        return $out;
    }

    public function delete() {
        if(!$this->hasRows() || !is_array($this->getRows())) {
            throw new ModelException('Table rows missing from constructor.');
        }
        $primaryKey = $this->table->getPrimary($this->table->getColumnByIndex(0));
        $primaryValue = array_values($this->getRows());
        $primaryValue = $primaryValue[0];
        if($primaryKey && $primaryValue) {
            $sql = sprintf('DELETE FROM `%s` WHERE `%s` = %s;', $this->table->getName(), $primaryKey->getName(), DB::FormatQuery('%s', array($primaryValue)));
            return $this->AffectedRows($sql);
        }
        return null;
    }

    public function exists() {
        $primaryKey = $this->table->getPrimary($this->table->getColumnByIndex(0));
        $primaryValue = array_values($this->getRows());
        $primaryValue = $primaryValue[0];
        if($primaryKey && $primaryValue) {
            $sql = sprintf('SELECT %s FROM `%s` WHERE `%s` = %s;', $primaryKey->getName(), $this->table->getName(), $primaryKey->getName(), DB::FormatQuery('%s', array($primaryValue)));
            return self::Scalar($sql);
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
            foreach($this->table->getColumnNames() as $key=>$name) {
                $val = $this->getRow($name);
                $concat[]=DB::FormatQuery('`'.$name.'` = %s', array($val));
            }
            $sql = sprintf('UPDATE `%s` SET %s WHERE `%s` = \'%s\' LIMIT 1;', $this->table->getName(), join(', ', $concat), $primaryKey->getName(), DB::Escape($primaryValue));
            return $this->Query($sql);
        }
        return null;
    }

    protected function getCountSql($sql) {
        $sql = (strripos($sql, 'LIMIT') <= 1 && strripos($sql, 'LIMIT') > -1) ? substr($sql, 0, strripos($sql, 'LIMIT')) : $sql;
        $sql = (strripos($sql, 'OFFSET') <= 1 && strripos($sql, 'OFFSET') > -1) ? substr($sql, 0, strripos($sql, 'OFFSET')) : $sql;

        $primary = $this->table->getPrimary($this->table->getColumnByIndex(0));
        return sprintf('SELECT COUNT(`'.$primary->getName().'`) AS `Total` FROM (%s) AS `CountedResult`', $sql);
    }

    public static function Query($query, $rows = null, $page = null, $args = null) {
        /* $var $model Model */
        $model = static::OnCreateModel();
        $results = array();
        $fetchPage = false;
        $countSql = null;
        $query = str_ireplace('{table}', '`' . $model->getTable()->getName() . '`', $query);
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 3));
        if(!is_null($rows)){
            $page = (is_null($page)) ? 0 : $page;
            $countSql=$model->getCountSql(DB::FormatQuery($query, $args));
            $query .= sprintf(' LIMIT %s, %s',($page*$rows), $rows);
            $fetchPage = true;
        }
        $sql = DB::FormatQuery($query, $args);
        try {
            $query = DB::getInstance()->query($sql);
        } catch(DBException $e) {
            if($e->getCode() == 1146 && $model->getAutoCreateTable()) {
                $model->getTable()->create();
                return $model::Query($query, $rows, $page, $args);
            }
            throw $e;
        }
        if($query) {
            $results['data']['numFields'] = isset($query->field_count) ? $query->field_count : 0;
            $results['data']['numRows']=isset($query->num_rows) ? $query->num_rows : 0;
            $results['insertId']=isset($query->insert_id) ? $query->insert_id : null;
            $results['affectedRows']=isset($query->affected_rows) ? $query->affected_rows : 0;
            $results['query'][]=$sql;
            if($results['data']['numRows'] > 0) {
                while(($row = $query->fetch_assoc()) != false) {
                    $obj = static::OnCreateModel();
                    $obj->setRows($row);
                    $results['data']['rows'][]=$obj;
                }
            }
            if($fetchPage) {
                $results['query'][] = $countSql;
                $maxRows = DB::getInstance()->scalar($countSql);
                $results['data']['page']=$page;
                $results['data']['rowsPerPage']=$rows;
                $results['data']['maxRows']=intval($maxRows);
            }
            $model->setResults($results);
        }
        return $model;
    }

    /**
     * Returns model instance
     * @return static
     */
    protected static function OnCreateModel() {
        $caller=get_called_class();
        return new $caller();
    }

    public static function FetchAll($query, $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 1));
        return self::Query($query, null, null, $args);
    }

    public static function FetchAllPage($query, $skip = null, $rows = null, $args=null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 3));
        $skip = (is_null($skip)) ? 0 : $skip;
        $model = static::OnCreateModel();
        try {
            $maxRows = DB::getInstance()->scalar($model->getCountSql(DB::FormatQuery($query, $args)));
            if(!is_null($skip) && !is_null($rows)) {
                $query = $query.' LIMIT ' . $skip . ',' . $rows;
            }
            $model = self::Query($query, null, null, $args);
        } catch(DBException $e) {
            if($e->getCode() == 1146 && $model->getAutoCreateTable()) {
                $model->getTable()->create();
                return model::FetchAllPage($query, $skip, $rows, $args);
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

    public static function FetchPage($query, $rows = 10, $page = 0, $args=null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 3));
        return self::Query($query, $rows, $page, $args);
    }

    public static function FetchOne($query, $args=null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 1));
        $model =  self::Query($query . ((stripos($query, 'LIMIT') > 0) ? '' : ' LIMIT 1'), null, null, $args);
        if($model->hasRows()){
            $results = $model->getResults();
            if(isset($results['data']['rows'])) {
                return $results['data']['rows'][0];
            }
        }

        return $model;
    }

    public static function Insert($query, $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 1));
        $model=static::OnCreateModel();
        try {
            $model->setResults(array('insertId' => DB::getInstance()->insert($query, $args)));
        } catch(DBException $e) {
            if($e->getCode() == 1146 && $model->getAutoCreateTable()) {
                $model->getTable()->create();
                return $model::Insert($query, $args);
            }
            throw $e;
        }
        return $model;
    }

    public static function AffectedRows($query, $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 1));
        $model=static::OnCreateModel();
        try {
            $model->setResults(array('affectedRows' => DB::getInstance()->affectedRows($query, $args)));
        } catch(DBException $e) {
            if($e->getCode() == 1146 && $model->getAutoCreateTable()) {
                $model->getTable()->create();
                return $model::AffectedRows($query, $args);
            }
            throw $e;
        }
        return $model;
    }

    public static function NonQuery($query, $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 1));
        $class = static::OnCreateModel();
        $query = str_ireplace('{table}', '`' . $class->getTable()->getName() . '`', $query);
        try {
            DB::getInstance()->nonQuery($query, $args);
        } catch(DBException $e) {
            if($e->getCode() == 1146 && $class->getAutoCreateTable()) {
                $class->getTable()->create();
                return $class::NonQuery($query, $args);
            }
            throw $e;
        }
        return null;
    }

    public static function Scalar($query, $args = null) {
        $args = (is_null($args) || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 1));
        $class = static::OnCreateModel();
        $query = str_ireplace('{table}', '`' . $class->getTable()->getName() . '`', $query);
        try {
            return DB::getInstance()->scalar($query, $args);
        }catch(DBException $e) {
            if($e->getCode() == 1146 && $class->getAutoCreateTable()) {
                $class->getTable()->create();
                return $class::Scalar($query, $args);
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
        return (PhpInteger::isInteger($data)) ? intval($data) : $data;
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
     * @return self
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

    public function setNumRow($numRows) {
        $this->results['data']['numRows'] = $numRows;
    }

    public function getNumRows() {
        return isset($this->results['data']['numRows']) ? $this->results['data']['numRows'] : 0;
    }

    public function getNumFields( ){
        return isset($this->results['data']['numFields']) ? $this->results['data']['numFields'] : 0;
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

    public function getInsertId() {
        return (isset($this->results['insertId']) ? $this->results['insertId'] : null);
    }

    public function setTable($table) {
        $this->table = $table;
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

        /*$column = in_array(strtolower($name), $this->table->getColumnNames(true));
        if($column) {
            $column = $this->table->getColumn($name);
            $this->results['data']['rows'][$column->getName()] = $value;
        } else {
            throw new ModelException(sprintf('Unknown field %s in table %s', $name, $this->table->getName()));
        }*/
    }

    /**
     * Sets post data from post variable.
     * @param array $data
     */
    public function setPostData($data){
        if($data && count($data) > 0) {
            foreach($data as $key=>$value){
                $this->__set($key, $value);
            }
        }
    }

    public function __call($name, $args=null) {
        if(!method_exists($this, $name)){
            $index = substr($name, 3, strlen($name));
            switch(strtolower(substr($name, 0, 3))){
                case 'get':
                    return $this->__get($index);
                    break;
                case 'set':
                    $this->__set($index, $args[0]);
                    return true;
                    break;
            }
            $debug=debug_backtrace();
            throw new ModelException(sprintf('Unknown method: %s in %s on line %s', $name, $debug[0]['file'], $debug[0]['line']));
        }
        return call_user_func_array($name, $args);
    }

    public function setAutoCreateTable($bool) {
        $this->autoCreate = $bool;
    }

    public function getAutoCreateTable() {
        return $this->autoCreate;
    }
}