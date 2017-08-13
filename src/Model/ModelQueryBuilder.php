<?php

namespace Pecee\Model;

use Pecee\Model\Exceptions\ModelException;
use Pecee\Model\Exceptions\ModelNotFoundException;
use Pecee\Pixie\Exception;
use Pecee\Str;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;

trait ModelQueryBuilder
{
    /**
     * @var QueryBuilderHandler
     */
    protected $query;

    protected function createInstance(\stdClass $item)
    {
        $model = new static;
        $model->mergeRows((array)$item);
        $model->onInstanceCreate();

        return $model;
    }

    protected function createCollection(array $items)
    {
        $collection = new ModelCollection($items);
        $collection->setType(static::class);

        return $collection;
    }

    public function prefix($prefix)
    {
        $this->query->addPrefix($this->getTable(), $prefix);

        return $this;
    }

    public function limit($limit)
    {
        $this->query->limit($limit);

        return $this;
    }

    public function skip($skip)
    {
        $this->query->offset($skip);

        return $this;
    }

    public function take($amount)
    {
        return $this->limit($amount);
    }

    public function offset($offset)
    {
        return $this->skip($offset);
    }

    public function where($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->where($key, $operator, $value);

        return $this;
    }

    public function whereIn($key, $values)
    {
        $this->query->whereIn($key, $values);

        return $this;
    }

    public function whereNot($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->whereNot($key, $operator, $value);

        return $this;
    }

    public function whereNotIn($key, $values)
    {
        $this->query->whereNotIn($key, $values);

        return $this;
    }

    public function whereNull($key)
    {
        $this->query->whereNull($key);

        return $this;
    }

    public function whereNotNull($key)
    {
        $this->query->whereNotNull($key);

        return $this;
    }

    public function whereBetween($key, $valueFrom, $valueTo)
    {
        $this->query->whereBetween($key, $valueFrom, $valueTo);

        return $this;
    }

    public function orWhere($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->orWhere($key, $operator, $value);

        return $this;
    }

    public function orWhereIn($key, $values)
    {
        $this->query->orWhereIn($key, $values);

        return $this;
    }

    public function orWhereNotIn($key, $values)
    {
        $this->query->orWhereNotIn($key, $values);

        return $this;
    }

    public function orWhereNot($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->orWhereNot($key, $operator, $value);

        return $this;
    }

    public function orWhereNull($key)
    {
        $this->query->orWhereNull($key);

        return $this;
    }

    public function orWhereNotNull($key)
    {
        $this->query->orWhereNotNull($key);

        return $this;
    }

    public function orWhereBetween($key, $valueFrom, $valueTo)
    {
        $this->query->orWhereBetween($key, $valueFrom, $valueTo);

        return $this;
    }

    public function get()
    {
        return $this->all();
    }

    public function all()
    {
        $items = (array)$this->query->get();

        /* @var $model Model */
        $models = [];

        if (count($items)) {
            foreach ($items as $item) {
                $models[] = $this->createInstance($item);
            }
        }

        return $this->createCollection($models);
    }

    public function find($id)
    {
        $item = $this->query->where($this->getPrimary(), '=', $id)->first();
        if ($item !== null) {
            return $this->createInstance($item);
        }

        return null;
    }

    public function findOrFail($id)
    {
        $item = $this->find($id);
        if ($item === null) {
            throw new ModelNotFoundException(ucfirst(Str::camelize($this->getTable())) . ' was not found');
        }

        return $item;
    }

    public function first()
    {
        $item = $this->query->first();
        if ($item !== null) {
            return $this->createInstance($item);
        }

        return null;
    }

    public function firstOrFail()
    {
        $item = $this->first();
        if ($item === null) {
            throw new ModelNotFoundException(ucfirst(Str::camelize($this->getTable())) . ' was not found');
        }

        return $item;
    }

    public function count()
    {
        return $this->query->count();
    }

    public function max($field)
    {
        $result = $this->query->select($this->query->raw('MAX(' . $field . ') AS max'))->get();

        return (int)$result[0]->max;
    }

    public function sum($field)
    {
        $result = $this->query->select($this->query->raw('SUM(' . $field . ') AS sum'))->get();

        return (int)$result[0]->sum;
    }

    protected function getValidData(array $data)
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->getColumns(), true) === true) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    public function update(array $data = [])
    {
        if (count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $this->query->update($data);

        return $this;
    }

    public function create(array $data = [])
    {
        $data = array_merge($this->getRows(), $this->getValidData($data));

        if (count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $id = $this->query->insert($data);

        if ($id) {

            $this->mergeRows($data);
            $this->{$this->getPrimary()} = $id;

            return $this;
        }

        return false;
    }

    public function firstOrCreate(array $data = [])
    {
        $item = $this->first();

        if ($item === null) {
            $item = $this->createInstance((object)$data);
        }

        $item->mergeRows($data);
        $item->save();

        return $item;
    }

    public function firstOrNew(array $data = [])
    {
        $item = $this->first();

        if ($item === null) {
            return $this->createInstance((object)$data);
        }

        return $item;
    }

    public function destroy($ids)
    {
        $this->query->whereIn('id', $ids)->delete();

        return $this;
    }

    public function select($fields)
    {
        $this->query->select($fields);

        return $this;
    }

    public function groupBy($field)
    {
        $this->query->groupBy($field);

        return $this;
    }

    public function orderBy($fields, $defaultDirection = 'ASC')
    {
        $this->query->orderBy($fields, $defaultDirection);

        return $this;
    }

    public function join($table, $key, $operator = null, $value = null, $type = 'inner')
    {
        $this->query->join($table, $key, $operator, $value, $type);

        return $this;
    }

    public function raw($value, array $bindings = [])
    {
        return $this->query->raw($value, $bindings);
    }

    public function subQuery(Model $model, $alias = null)
    {
        return $this->query->subQuery($model->getQuery(), $alias);
    }

    /**
     * @return QueryBuilderHandler
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * Get unique identifier for current query
     * @return string
     * @throws Exception
     */
    public function getQueryIdentifier()
    {
        return md5(static::class . $this->getQuery()->getQuery()->getRawSql());
    }

}