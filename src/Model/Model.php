<?php

namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;
use Pecee\Str;

abstract class Model implements \IteratorAggregate
{
    use ModelQueryBuilder;

    protected $table;
    protected $results = ['rows' => []];

    protected $primary = 'id';
    protected $hidden = [];
    protected $with = [];
    protected $rename = [];
    protected $join = [];
    protected $columns = [];
    protected $timestamps = true;

    public function __construct()
    {
        // Set table name if its not already defined
        if ($this->table === null) {
            $name = explode('\\', static::class);
            $name = str_ireplace('model', '', end($name));
            $this->table = strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $name));
        }

        if ($this->timestamps === true) {
            $this->columns = array_merge($this->columns, [
                'created_at',
                'updated_at',
            ]);
            $this->created_at = Carbon::now();
        }
    }

    public function newQuery($table = null)
    {
        $this->query = (new QueryBuilderHandler())->table($table);

        if (app()->getDebugEnabled() === true) {

            $this->query->registerEvent('before-*', $table,
                function (QueryBuilderHandler $qb) {
                    debug('START QUERY: ' . $qb->getQuery()->getRawSql());
                });

            $this->query->registerEvent('after-*', $table,
                function (QueryBuilderHandler $qb) {
                    debug('END QUERY: ' . $qb->getQuery()->getRawSql());
                });
        }
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

    /**
     * @param \stdClass $item
     * @return static
     */
    public function getInstance(\stdClass $item)
    {
        return new static;
    }

    public function onInstanceCreate()
    {
        $this->joinData();
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

        if ($data !== null) {

            /* Only save valid columns */
            $data = array_filter($data, function ($key) {
                return (in_array($key, $this->columns, true) === true);
            }, ARRAY_FILTER_USE_KEY);

            $updateData = array_merge($updateData, $data);
        }

        $this->mergeRows($updateData);

        if ($this->exists() === true) {

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
                $this->{$this->primary} = static::getQuery()->insert($updateData);
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
        return (bool)(isset($this->results['rows']) && count($this->results['rows']) > 0);
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
        return array_key_exists(strtolower($name), $this->results['rows']);
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
            return (array_search($a, $this->columns, true) > array_search($b, $this->columns, true));
        });
    }

    public function toArray(array $filter = [])
    {
        $rows = $this->getRows();

        if ($rows !== null) {
            foreach ($rows as $key => $row) {
                $key = isset($this->rename[$key]) ? $this->rename[$key] : $key;
                if (in_array($key, $this->hidden, true) === true || (count($filter) && in_array($key, $filter, true) === false)) {
                    unset($rows[$key]);
                    continue;
                }
                $rows[$key] = $this->parseArrayData($row);
            }

            $this->orderArrayRows($rows);
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

        return $rows;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param $method
     * @param $parameters
     * @return static
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        if (method_exists($instance->queryable, $method) === true) {
            return call_user_func_array([$instance, $method], $parameters);
        }

        return null;
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

}