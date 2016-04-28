<?php
namespace Pecee\Model;

use Pecee\Model\Exceptions\ModelException;
use Pecee\Model\Exceptions\ModelNotFoundException;
use Pixie\QueryBuilder\QueryBuilderHandler;

class ModelQueryBuilder {

    protected static $instance;

    /**
     * @var Model
     */
    protected $model;
    /**
     * @var QueryBuilderHandler
     */
    protected $query;

    public function __construct(Model $model, $table) {
        $this->model = $model;
        $this->query = (new QueryBuilderHandler())->table($table);
    }

    protected function createInstance(\stdClass $item) {

        $model = get_class($this->model);

        /* @var $model Model */
        $model = new $model();
        $model->mergeRows((array)$item);
        $model->onInstanceCreate();

        return $model;
    }

    public function limit($limit) {
        $this->query->limit($limit);
        return $this->model;
    }

    public function skip($skip) {
        $this->query->offset($skip);
        return $this->model;
    }

    public function take($amount) {
        return $this->limit($amount);
    }

    public function offset($offset) {
        return $this->skip($offset);
    }

    public function where($key, $operator = null, $value = null) {
        $this->query->where($key, $operator, $value);
        return $this->model;
    }

    public function whereIn($key, array $values) {
        $this->query->whereIn($key, $values);
        return $this->model;
    }

    public function whereNot($key, $operator = null, $value = null) {
        $this->query->whereNot($key, $operator, $value);
        return $this->model;
    }

    public function whereNotIn($key, array $values) {
        $this->query->whereNotIn($key, $values);
        return $this->model;
    }

    public function whereNull($key) {
        $this->query->whereNull($key);
        return $this->model;
    }

    public function whereNotNull($key) {
        $this->query->whereNotNull($key);
        return $this->model;
    }

    public function whereBetween($key, $valueFrom, $valueTo) {
        $this->query->whereBetween($key, $valueFrom, $valueTo);
        return $this->model;
    }

    public function orWhere($key, $operator = null, $value = null) {
        $this->query->orWhere($key, $operator, $value);
        return $this->model;
    }

    public function orWhereIn($key, array $values) {
        $this->query->orWhereIn($key, $values);
        return $this->model;
    }

    public function orWhereNotIn($key, array $values) {
        $this->query->orWhereNotIn($key, $values);
        return $this->model;
    }

    public function orWhereNot($key, $operator = null, $value = null) {
        $this->query->orWhereNot($key, $operator, $value);
        return $this->model;
    }

    public function orWhereNull($key) {
        $this->query->orWhereNull($key);
        return $this->model;
    }

    public function orWhereNotNull($key) {
        $this->query->orWhereNotNull($key);
        return $this->model;
    }

    public function orWhereBetween($key, $valueFrom, $valueTo) {
        $this->query->orWhereBetween($key, $valueFrom, $valueTo);
        return $this->model;
    }

    public function get() {
        return $this->all();
    }

    public function all() {
        $collection = (array)$this->query->get();

        $class = get_class($this->model);
        /* @var $model Model */
        $model = new $class();

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
        $item = $this->query->where($this->model->getPrimary(), '=', $id)->first();
        if($item !== null) {
            return $this->createInstance($item);
        }
        return null;
    }

    public function findOrFail($id) {
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
            if(in_array($key, $this->model->getColumns())) {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public function update(array $data = array()) {
        $data = $this->getValidData($data);

        if(count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        // Remove primary key
        unset($data[$this->model->getPrimary()]);

        $this->model->instance()->getQuery()->where($this->model->getPrimary(), '=', $this->model->{$this->model->getPrimary()})->update($data);
        return $this->model;
    }

    public function create(array $data = array()) {

        $data = array_merge($this->model->getRows(), $this->getValidData($data));

        if(count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $id = $this->query->insert($data);

        if($id) {

            $this->model->mergeRows($data);
            $this->model->{$this->model->getPrimary()} = $id;
            return $this->model;
        }

        return false;
    }

    public function firstOrCreate(array $data) {
        $item = $this->first();
        if($item === null) {
            $item = $this->createInstance((object)$data);
        }
        $item->mergeRows($data);
        $item->save();
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
        return $this->model;
    }

    public function select(array $fields) {
        $this->query->select($fields);
        return $this->model;
    }

    public function groupBy($field) {
        $this->query->groupBy($field);
        return $this->model;
    }

    public function orderBy($fields, $defaultDirection = 'ASC') {
        $this->query->orderBy($fields, $defaultDirection);
        return $this->model;
    }

    public function join($table, $key, $operator = null, $value = null, $type = 'inner') {
        $this->query->join($table, $key, $operator, $value, $type);
        return $this->model;
    }

    public function raw($value, array $bindings = array()) {
        return $this->query->raw($value, $bindings);
    }

    public function subQuery(Model $model, $alias = null) {
        return $this->query->subQuery($model->getQuery(), $alias);
    }

    /**
     * @return Model
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel(Model $model) {
        $this->model = $model;
    }

    /**
     * @return QueryBuilderHandler
     */
    public function getQuery() {
        return $this->query;
    }

}