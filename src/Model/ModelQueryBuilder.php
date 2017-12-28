<?php

namespace Pecee\Model;

use Pecee\Model\Exceptions\ModelException;
use Pecee\Model\Exceptions\ModelNotFoundException;
use Pecee\Pixie\Exception;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;
use Pecee\Pixie\QueryBuilder\QueryObject;
use Pecee\Pixie\QueryBuilder\Raw;
use Pecee\Str;

class ModelQueryBuilder
{
    protected static $instance;

    /**
     * @var Model
     */
    protected $model;
    /**
     * @var QueryBuilderHandler
     */
    protected $query;

    /**
     * ModelQueryBuilder constructor.
     * @param Model $model
     * @throws Exception
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->query = (new QueryBuilderHandler())->table($model->getTable());

        if (app()->getDebugEnabled() === true) {

            $this->query->registerEvent('before-*', $model->getTable(),
                function (QueryBuilderHandler $qb, QueryObject $qo) {
                    debug('START QUERY: ' . $qo->getRawSql());
                });

            $this->query->registerEvent('after-*', $model->getTable(),
                function (QueryBuilderHandler $qb, QueryObject $qo) {
                    debug('END QUERY: ' . $qo->getRawSql());
                });
        }
    }

    protected function createInstance(\stdClass $item)
    {
        /* @var $model Model */
        $model = get_class($this->model);
        $model = new $model();
        $model->with($this->model->getWith());
        $model->mergeRows((array)$item);
        $model->setOriginalRows((array)$item);
        $model->onInstanceCreate();

        return $model;
    }

    protected function createCollection(array $items)
    {
        $collection = $this->model->onCollectionCreate($items);
        $collection->setType(static::class);

        return $collection;
    }

    public function alias($alias)
    {
        $this->query->alias($alias, $this->model->getTable());

        return $this->model;
    }

    public function limit($limit)
    {
        $this->query->limit($limit);

        return $this->model;
    }

    public function skip($skip)
    {
        $this->query->offset($skip);

        return $this->model;
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

        return $this->model;
    }

    public function whereIn($key, $values)
    {
        $this->query->whereIn($key, $values);

        return $this->model;
    }

    public function whereNot($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->whereNot($key, $operator, $value);

        return $this->model;
    }

    public function whereNotIn($key, $values)
    {
        $this->query->whereNotIn($key, $values);

        return $this->model;
    }

    public function whereNull($key)
    {
        $this->query->whereNull($key);

        return $this->model;
    }

    public function whereNotNull($key)
    {
        $this->query->whereNotNull($key);

        return $this->model;
    }

    public function whereBetween($key, $valueFrom, $valueTo)
    {
        $this->query->whereBetween($key, $valueFrom, $valueTo);

        return $this->model;
    }

    public function orWhere($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->orWhere($key, $operator, $value);

        return $this->model;
    }

    public function orWhereIn($key, $values)
    {
        $this->query->orWhereIn($key, $values);

        return $this->model;
    }

    public function orWhereNotIn($key, $values)
    {
        $this->query->orWhereNotIn($key, $values);

        return $this->model;
    }

    public function orWhereNot($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->orWhereNot($key, $operator, $value);

        return $this->model;
    }

    public function orWhereNull($key)
    {
        $this->query->orWhereNull($key);

        return $this->model;
    }

    public function orWhereNotNull($key)
    {
        $this->query->orWhereNotNull($key);

        return $this->model;
    }

    public function orWhereBetween($key, $valueFrom, $valueTo)
    {
        $this->query->orWhereBetween($key, $valueFrom, $valueTo);

        return $this->model;
    }

    /**
     * @throws Exception
     */
    public function get()
    {
        return $this->all();
    }

    /**
     * @throws Exception
     */
    public function all()
    {
        $items = (array)$this->query->get();

        /* @var $model Model */
        $models = [];

        foreach ($items as $item) {
            $models[] = $this->createInstance($item);
        }

        return $this->createCollection($models);
    }

    /**
     * @throws Exception
     */
    public function find($id)
    {
        $item = $this->query->where($this->model->getPrimary(), '=', $id)->first();
        if ($item !== null) {
            return $this->createInstance($item);
        }

        return null;
    }

    /**
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function findOrFail($id)
    {
        $item = $this->find($id);
        if ($item === null) {
            throw new ModelNotFoundException(ucfirst(Str::camelize($this->model->getTable())) . ' was not found');
        }

        return $item;
    }

    /**
     * @throws Exception
     */
    public function first()
    {
        $item = $this->query->first();
        if ($item !== null) {
            return $this->createInstance($item);
        }

        return null;
    }

    /**
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function firstOrFail()
    {
        $item = $this->first();
        if ($item === null) {
            throw new ModelNotFoundException(ucfirst(Str::camelize($this->model->getTable())) . ' was not found');
        }

        return $item;
    }

    /**
     * @throws Exception
     */
    public function count()
    {
        return $this->query->count();
    }

    /**
     * @throws Exception
     */
    public function max($field)
    {
        $result = $this->query->select($this->query->raw('MAX(' . $field . ') AS max'))->get();

        return (int)$result[0]->max;
    }

    /**
     * @throws Exception
     */
    public function sum($field)
    {
        $result = $this->query->select($this->query->raw('SUM(' . $field . ') AS sum'))->get();

        return (int)$result[0]->sum;
    }

    protected function getValidData(array $data)
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->model->getColumns(), true) === true) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @throws Exception
     * @throws ModelException
     */
    public function update(array $data = [])
    {
        if (count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $this->query->update($data);

        return $this->model;
    }

    /**
     * @throws Exception
     * @throws ModelException
     */
    public function create(array $data = [])
    {
        $data = array_merge($this->model->getRows(), $this->getValidData($data));

        if (count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $id = $this->query->insert($data);

        if ($id) {

            $this->model->mergeRows($data);
            $this->model->{$this->model->getPrimary()} = $id;

            return $this->model;
        }

        return false;
    }

    /**
     * @throws Exception
     * @throws ModelException
     */
    public function firstOrCreate(array $data = [])
    {
        $item = $this->first();

        if ($item === null) {
            $item = $this->createInstance((object)$data);
            $item->setOriginalRows([]);
            $item->save();
        }

        return $item;
    }

    /**
     * @throws Exception
     */
    public function firstOrNew(array $data = [])
    {
        $item = $this->first();

        if ($item === null) {
            $item = $this->createInstance((object)$data);
            $item->setOriginalRows([]);
        }

        return $item;
    }

    /**
     * @param array $ids
     * @return Model
     * @throws Exception
     */
    public function destroy($ids)
    {
        $this->query->whereIn('id', $ids)->delete();

        return $this->model;
    }

    public function select($fields)
    {
        $this->query->select($fields);

        return $this->model;
    }

    public function groupBy($field)
    {
        $this->query->groupBy($field);

        return $this->model;
    }

    public function orderBy($fields, $defaultDirection = 'ASC')
    {
        $this->query->orderBy($fields, $defaultDirection);

        return $this->model;
    }

    /**
     * @throws Exception
     */
    public function join($table, $key, $operator = null, $value = null, $type = 'inner')
    {
        $this->query->join($table, $key, $operator, $value, $type);

        return $this->model;
    }

    public function raw($value, array $bindings = [])
    {
        return $this->query->raw($value, $bindings);
    }

    /**
     * @param Model $model
     * @param null $alias
     * @return Raw
     * @throws Exception
     */
    public function subQuery(Model $model, $alias = null)
    {
        return $this->query->subQuery($model->getQuery(), $alias);
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param Model $model
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
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

    public function __sleep()
    {
        return ['model'];
    }

    /**
     * @throws Exception
     */
    public function __wakeup()
    {
        $this->query = (new QueryBuilderHandler())->table($this->model->getTable());
    }

}