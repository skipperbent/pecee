<?php

namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;
use Pecee\Str;

/**
 *
 * Helper docs to support both static and non-static calls, which redirects to ModelQueryBuilder.
 *
 * @method $this alias(string $prefix)
 * @method $this limit(int $id)
 * @method $this skip(int $id)
 * @method $this take(int $id)
 * @method $this offset(int $id)
 * @method $this where(string $key, string $operator = null, string $value = null)
 * @method $this whereIn(string $key, array | object $values)
 * @method $this whereNot(string $key, string $operator = null, string $value = null)
 * @method $this whereNotIn(string $key, array | object $values)
 * @method $this whereNull(string $key)
 * @method $this whereNotNull(string $key)
 * @method $this whereBetween(string $key, string $valueFrom, string $valueTo)
 * @method $this orWhere(string $key, string $operator = null, string $value = null)
 * @method $this orWhereIn(string $key, array | object $values)
 * @method $this orWhereNotIn(string $key, array | object $values)
 * @method $this orWhereNot(string $key, string $operator = null, string $value = null)
 * @method $this orWhereNull(string $key)
 * @method $this orWhereNotNull(string $key)
 * @method $this orWhereBetween(string $key, string $valueFrom, string $valueTo)
 * @method ModelCollection get()
 * @method ModelCollection all()
 * @method $this find(string $id)
 * @method $this findOrFail(string $id)
 * @method $this first()
 * @method $this firstOrFail()
 * @method $this count()
 * @method $this max(string $field)
 * @method $this sum(string $field)
 * @method $this update(array $data)
 * @method $this create(array $data)
 * @method $this firstOrCreate(array $data)
 * @method $this firstOrNew(array $data)
 * @method $this destroy(array | object $ids)
 * @method $this select(array | object $fields)
 * @method $this groupBy(string $field)
 * @method $this orderBy(string $field, string $defaultDirection = 'ASC')
 * @method $this join(string|array $table, string $key, string $operator = null, string $value = null, string $type = 'inner'))
 * @method QueryBuilderHandler getQuery()
 * @method string raw(string $value, array $bindings = [])
 * @method string subQuery(Model $model, string $alias = null)
 * @method string getQueryIdentifier()
 */
abstract class Model implements \IteratorAggregate
{
    protected $table;
    protected $results = ['rows' => [], 'original_rows' => []];
    protected $primary = 'id';
    protected $hidden = [];
    protected $with = [];
    protected $rename = [];
    protected $join = [];
    protected $columns = [];
    protected $timestamps = true;

    /**
     * @var ModelQueryBuilder
     */
    protected $queryable;

    public function __construct()
    {
        // Set table name if its not already defined
        if ($this->table === null) {
            $name = explode('\\', static::class);
            $name = str_ireplace('model', '', end($name));
            $this->table = strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $name));
        }

        $this->queryable = new ModelQueryBuilder($this, $this->table);

        if ($this->timestamps === true) {
            $this->columns = array_merge($this->columns, [
                'updated_at',
                'created_at',
            ]);
            $this->created_at = Carbon::now();
        }
    }

    public function newQuery($table = null)
    {
        $model = new static();
        $model->setQuery(new ModelQueryBuilder($this, $table));

        return $model;
    }

    /**
     * Create new instance.
     *
     * @return static
     */
    public static function instance()
    {
        return new static();
    }

    public function onInstanceCreate()
    {
        $this->joinData();
    }

    public function onCollectionCreate($items)
    {
        return new ModelCollection($items);
    }

    protected function joinData()
    {
        if (count($this->join)) {
            for ($i = 0, $max = count($this->join); $i < $max; $i++) {
                $join = $this->join[$i];
                $this->{$join} = $this->{Str::camelize($join)}();
            }
        }
    }

    /**
     * Save item
     * @see \Pecee\Model\Model::save()
     * @param array|null $data
     * @return static
     * @throws ModelException|\Pecee\Pixie\Exception
     */
    public function save(array $data = null)
    {
        if (is_array($this->columns) === false) {
            throw new ModelException('Columns property not defined.');
        }

        $updateData = [];
        foreach ($this->columns as $column) {
            $updateData[$column] = $this->{$column};
        }

        $originalRows = $this->getOriginalRows();

        if ($data !== null) {

            /* Only save valid columns */
            $data = array_filter($data, function ($key) {
                return (in_array($key, $this->columns, true) === true);
            }, ARRAY_FILTER_USE_KEY);

            $updateData = array_merge($updateData, $data);
        }

        foreach ($updateData as $key => $value) {
            if (array_key_exists($key, $originalRows) === true && $originalRows[$key] === $value) {
                unset($updateData[$key]);
            }
        }

        if (count($updateData) === 0) {
            return $this;
        }

        $this->mergeRows($updateData);

        if (count($originalRows) > 0 || $this->exists() === true) {

            if ($this->timestamps) {
                $this->updated_at = Carbon::now()->toDateTimeString();
            }

            if (isset($updateData[$this->getPrimary()])) {
                // Remove primary key
                unset($updateData[$this->getPrimary()]);
            }

            static::instance()->where($this->getPrimary(), '=', $this->{$this->getPrimary()})->update($updateData);
        } else {

            $updateData = array_filter($updateData, function ($value) {
                return $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            if ($this->{$this->primary} === null) {
                $this->{$this->primary} = static::instance()->getQuery()->insert($updateData);
            } else {
                static::instance()->getQuery()->insert($updateData);
            }
        }

        return $this;
    }

    public function delete()
    {
        if (is_array($this->columns) === false) {
            throw new ModelException('Columns property not defined.');
        }

        if ($this->{$this->primary} !== null) {
            $this->queryable->where($this->primary, '=', $this->{$this->primary});
        }

        return $this->queryable->getQuery()->delete();
    }

    public function exists()
    {
        if ($this->{$this->primary} === null) {
            return false;
        }

        $id = static::instance()->select([$this->primary])->where($this->primary, '=', $this->{$this->primary})->first();

        if ($id !== null) {
            $this->{$this->primary} = $id->{$this->primary};

            return true;
        }

        return false;
    }

    public function hasRows()
    {
        return (isset($this->results['rows']) && count($this->results['rows']) > 0);
    }

    /**
     * Get row
     * @param int $key
     * @return static
     */
    public function getRow($key)
    {
        return ($this->hasRows() === true && isset($this->results['rows'][$key])) ? $this->results['rows'][$key] : null;
    }

    public function setRow($key, $value)
    {
        $this->results['rows'][$key] = $value;
    }

    public function setRows(array $rows)
    {
        $this->results['rows'] = $rows;
    }

    public function mergeRows(array $rows)
    {
        $this->results['rows'] = array_merge($this->results['rows'], $rows);
    }

    /**
     * Get rows
     * @return array
     */
    public function getRows()
    {
        return isset($this->results['rows']) ? $this->results['rows'] : [];
    }

    public function setResults($results)
    {
        $this->results = $results;
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function __get($name)
    {
        return isset($this->results['rows'][$name]) ? $this->results['rows'][$name] : null;
    }

    public function __set($name, $value)
    {
        $this->results['rows'][strtolower($name)] = $value;
    }

    public function __isset($name)
    {
        return in_array($name, $this->columns, true);
    }

    public function getPrimary()
    {
        return $this->primary;
    }

    protected function parseArrayData($data)
    {
        if (is_array($data) === true) {
            $out = [];
            foreach ((array)$data as $d) {
                $out[] = $this->parseArrayData($d);
            }

            return $out;
        }

        $encoding = mb_detect_encoding($data, 'UTF-8', true);
        $data = (is_array($data) === false && ($encoding === false || strtolower($encoding) !== 'utf-8')) ? mb_convert_encoding($data, 'UTF-8', $encoding) : $data;

        if (is_float($data) === true) {
            return (float)$data;
        }

        if (is_bool($data) === true || is_numeric($data) === true) {
            return (int)$data;
        }

        return $data;
    }

    protected function orderArrayRows(array &$rows)
    {
        uksort($rows, function ($a, $b) {
            return (array_search($a, $this->columns, true) < array_search($b, $this->columns, true) === true) ? -1 : 1;
        });
    }

    public function toArray(array $filter = [])
    {
        $rows = $this->getRows();

        foreach ($rows as $key => $row) {
            $key = isset($this->rename[$key]) ? $this->rename[$key] : $key;
            if (in_array($key, $this->hidden, true) === true || (count($filter) && in_array($key, $filter, true) === true)) {
                unset($rows[$key]);
                continue;
            }
            $rows[$key] = $this->parseArrayData($row);
        }

        if (count($this->getResults()) === 1) {
            foreach ($this->with as $with) {
                $method = Str::camelize($with);
                if (method_exists($this, $method) === false) {
                    throw new ModelException('Missing required method ' . $method);
                }
                $output = $this->$method();
                $with = isset($this->rename[$with]) ? $this->rename[$with] : $with;
                $rows[$with] = ($output instanceof self || $output instanceof ModelCollection) ? $output->toArray() : $output;
            }
        }

        $this->orderArrayRows($rows);

        return $rows;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return static|QueryBuilderHandler|null
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->queryable, $method) === true) {
            return $this->queryable->{$method}(...$parameters);
        }

        return null;
    }

    /**
     * Set original rows
     * @param array $rows
     * @return static $this
     */
    public function setOriginalRows(array $rows)
    {
        $this->results['original_rows'] = $rows;
    }

    public function getOriginalRows()
    {
        return $this->results['original_rows'];
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function setQuery(ModelQueryBuilder $query)
    {
        $this->queryable = $query;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getRows());
    }

    public function __clone()
    {
        $this->setQuery(clone $this->queryable);
    }

}