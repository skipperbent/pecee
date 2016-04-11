<?php
namespace Pecee\Model;

use Pecee\Integer;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Model\Exceptions\ModelNotFoundException;
use Pecee\Str;
use Pixie\QueryBuilder\QueryBuilderHandler;

abstract class Model implements \IteratorAggregate {

    protected $table;
    protected $results;

    protected $primary = 'id';
    protected $hidden = [];
    protected $with = [];
    protected $rename = [];
    protected $join = [];
    protected $columns = [];

    /**
     * @var QueryBuilderHandler
     */
    protected $query;

    public function __construct() {

        // Set table name if its not already defined
        if($this->table === null) {
            $name = explode('\\', get_called_class());
            $name = str_ireplace('model', '', end($name));
            $this->table = strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $name)) . 's';
        }

        $qb = new QueryBuilderHandler();
        $this->query = $qb->table($this->table);
        $this->results = array();
    }

    protected function createInstance(\stdClass $item) {
        $model = new static();
        $model->setRows((array)$item);
        return $model;
    }

    public function limit($limit) {
        $this->query->limit($limit);
        return $this;
    }

    public function skip($skip) {
        $this->query->offset($skip);
        return $this;
    }

    public function take($amount) {
        return $this->limit($amount);
    }

    public function offset($offset) {
        return $this->skip($offset);
    }

    public function where($key, $operator = null, $value = null) {
        $this->query->where($key, $operator, $value);
        return $this;
    }

    public function get() {
        return $this->all();
    }

    public function all() {
        $collection = (array)$this->query->get();

        $model = new static();

        $models = array();

        if(count($collection)) {
            foreach($collection as $item) {
                $models[] = $this->createInstance($item);
            }
        }

        $model->setResults(array('rows' => $models, 'collection' => true));

        return $model;
    }

    public function find($id) {
        $item = $this->query->where($this->primary, '=', $id)->first();
        if($item !== null) {
            return $this->createInstance($item);
        }
        return null;
    }

    public function findOrfail($id) {
        $item = $this->find($id);
        if($item === null) {
            throw new ModelNotFoundException('Item was not found');
        }
        return $item;
    }

    public function first() {
        $item = $this->query->first();
        if($item !== null) {
            return $this->createInstance($item);
        }
        return null;
    }

    public function firstOrFail() {
        $item = $this->first();
        if($item === null) {
            throw new ModelNotFoundException('Item was not found');
        }
        return $item;
    }

    public function count() {
        return $this->query->count();
    }

    public function max($field) {
        $result = $this->query->select($this->query->raw('MAX('. $field .') AS max'))->get();
        return (int)$result[0]->max;
    }

    public function sum($field) {
        $result = $this->query->select($this->query->raw('SUM('. $field .') AS sum'))->get();
        return (int)$result[0]->sum;
    }

    protected function getValidData($data) {
        $out = array();
        foreach($data as $key => $value) {
            if(in_array($key, $this->columns)) {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public function update(array $data) {
        $data = $this->getValidData($data);

        if(!isset($data[$this->primary])) {
            throw new ModelException('Primary identifier not defined.');
        }

        if(count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $this->query->update($data);
        $this->setRows($data);
        return $this;
    }

    public function create(array $data) {
        $data = $this->getValidData($data);

        if(!isset($data[$this->primary])) {
            throw new ModelException('Primary identifier not defined.');
        }

        if(count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $id = $this->query->insert($data);

        if($id) {

            $this->setRows($data);
            $this->{$this->primary} = $id;
            return $this;
        }

        return false;
    }

    public function firstOrCreate(array $data) {
        $item = $this->first();
        if($item === null) {
            $item = $this->createInstance((object)$data);
            $item->save();
        }
        return $item;
    }

    public function firstOrNew(array $data) {
        $item = $this->first();
        if($item === null) {
            return $this->createInstance((object)$data);
        }
        return $item;
    }

    public function destroy($ids) {
        $this->query->whereIn('id', $ids)->delete();
        return $this;
    }

    /**
     * Save item
     * @see Pecee\Model\Model::save()
     * @return static
     * @throws ModelException
     */
    public function save() {
        if(!is_array($this->columns)) {
            throw new ModelException('Columns property not defined.');
        }

        $data = array();
        foreach($this->columns as $key) {
            $data[$key] = $this->{$key};
        }

        if($this->exists()) {
            $this->query->where($this->primary, '=', $this->{$this->primary})->update($data);
        } else {
            $this->{$this->primary} = $this->query->insert($data);
        }

        return $this;
    }

    public function delete() {
        if(!is_array($this->columns)) {
            throw new ModelException('Columns property not defined.');
        }

        $this->query->where($this->primary, '=', $this->{$this->primary})->delete();
    }

    public function exists() {

        if($this->{$this->primary} === null) {
            return false;
        }

        return ($this->query->where($this->primary, '=', $this->{$this->primary})->count() > 0);
    }

    public function isCollection() {
        return (isset($this->results['collection']) && $this->results['collection'] === true);
    }

    public function hasRows() {
        return ($this->isCollection() && isset($this->results['rows']) && count($this->results['rows']) > 0);
    }

    /**
     * Get row
     * @param int $key
     * @return static
     */
    public function getRow($key) {
        return ($this->hasRows() && isset($this->results['rows'][$key])) ? $this->results['rows'][$key] : null;
    }

    public function setRow($key, $value) {
        $this->results['rows'][$key] = $value;
    }

    public function setRows(array $rows) {
        $this->results['rows'] = $rows;

        if(!$this->isCollection()) {
            if (count($this->join)) {
                foreach ($this->join as $join) {
                    $method = Str::camelize($join);
                    $this->{$join} = $this->$method();
                }
            }
        }
    }

    /**
     * Get rows
     * @return array|null
     */
    public function getRows() {
        return ($this->hasRows()) ? $this->results['rows'] : null;
    }

    public function setResults($results) {
        $this->results = $results;
    }

    public function getResults() {
        return $this->results;
    }

    public function getTable() {
        return $this->table;
    }

    public function __get($name) {
        return (isset($this->results['rows'][$name])) ? $this->results['rows'][$name] : null;
    }

    public function __set($name, $value) {
        $this->results['rows'][strtolower($name)] = $value;
    }

    public function getPrimary() {
        return $this->primary;
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

    public function toArray() {

        $rows = $this->results['rows'];

        if($rows && is_array($rows)) {
            foreach($rows as $key => $row){
                $key = (isset($this->rename[$key])) ? $this->rename[$key] : $key;
                if($row instanceof self) {
                    $rows[$key] = $row->toArray();
                } else {
                    if(in_array($key, $this->hidden)) {
                        unset($rows[$key]);
                        continue;
                    }
                    $rows[$key] = $this->parseArrayData($row);
                }
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
                if($output instanceof self) {
                    $rows[$with] = $output->toArray();
                } else {
                    $rows[$with] = $output;
                }
            }
            return $rows;
        }

        $arr = $rows;
        return $arr;
    }

    public function getQuery() {
        return $this->query;
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

    public static function __callStatic($name, $arguments)
    {
        // Note: value of $name is case sensitive.
        echo "Calling static method '$name' "
            . implode(', ', $arguments). "\n";
    }

}