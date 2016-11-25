<?php
namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\Integer;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Str;
use Pixie\QueryBuilder\QueryBuilderHandler;

/**
 *
 * Helper docs to support both static and non-static calls, which redirects to ModelQueryBuilder.
 *
 * @method static $this prefix(string $prefix)
 * @method static $this limit(int $id)
 * @method static $this skip(int $id)
 * @method static $this take(int $id)
 * @method static $this offset(int $id)
 * @method static $this where(string $key, string $operator = null, string $value = null)
 * @method static $this whereIn(string $key, array|object $values)
 * @method static $this whereNot(string $key, string $operator = null, string $value = null)
 * @method static $this whereNotIn(string $key, array|object $values)
 * @method static $this whereNull(string $key)
 * @method static $this whereNotNull(string $key)
 * @method static $this whereBetween(string $key, string $valueFrom, string $valueTo)
 * @method static $this orWhere(string $key, string $operator = null, string $value = null)
 * @method static $this orWhereIn(string $key, array|object $values)
 * @method static $this orWhereNotIn(string $key, array|object $values)
 * @method static $this orWhereNot(string $key, string $operator = null, string $value = null)
 * @method static $this orWhereNull(string $key)
 * @method static $this orWhereNotNull(string $key)
 * @method static $this orWhereBetween(string $key, string $valueFrom, string $valueTo)
 * @method static ModelCollection get()
 * @method static ModelCollection all()
 * @method static $this find(string $id)
 * @method static $this findOrfail(string $id)
 * @method static $this first()
 * @method static $this firstOrFail()
 * @method static $this count()
 * @method static $this max(string $field)
 * @method static $this sum(string $field)
 * @method static $this update(array $data)
 * @method static $this create(array $data)
 * @method static $this firstOrCreate(array $data)
 * @method static $this firstOrNew(array $data)
 * @method static $this destroy(array|object $ids)
 * @method static $this select(array|object $fields)
 * @method static $this groupBy(string $field)
 * @method static $this orderBy(string $field, string $defaultDirection = 'ASC')
 * @method static $this join(string $table, string $key, string $operator = null, string $value = null, string $type = 'inner'))
 * @method static QueryBuilderHandler getQuery()
 * @method static string raw(string $value, array $bindings = [])
 * @method static string subQuery(QueryBuilderHandler $queryBuilder, string $alias = null)
 */
abstract class Model implements \IteratorAggregate
{

    protected $table;
    protected $results;

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
            $name = explode('\\', get_called_class());
            $name = str_ireplace('model', '', end($name));
            $this->table = strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $name));
        }

        $this->queryable = new ModelQueryBuilder($this, $this->table);

        if (env('DEBUG')) {

            $this->queryable->getQuery()->registerEvent('before-*', $this->table,
                function (QueryBuilderHandler $qb) {
                    debug('START QUERY: ' . $qb->getQuery()->getRawSql());
                });

            $this->queryable->getQuery()->registerEvent('after-*', $this->table,
                function (QueryBuilderHandler $qb) {
                    debug('END QUERY: ' . $qb->getQuery()->getRawSql());
                });
        }

        $this->results = ['rows' => []];

        if ($this->timestamps) {
            $this->columns = array_merge($this->columns, ['created_at', 'updated_at']);
            $this->created_at = Carbon::now()->toDateTimeString();
        }
    }

    public function newQuery($table = null)
    {
        $model = $this->instance();
        $model->setQuery(new ModelQueryBuilder($this, $table));

        return $model;
    }

    /**
     * Create new instance
     * @return static
     */
    public static function new()
    {
        return new static();
    }

    /**
     * Create new instance.
     * Alias for static::new();
     *
     * @see static::new()
     * @return static
     */
    public static function instance()
    {
        return static::new();
    }

    public function onInstanceCreate()
    {
        if (count($this->join)) {
            foreach ($this->join as $join) {
                $method = Str::camelize($join);
                $this->{$join} = $this->$method();
            }
        }
    }

    /**
     * Save item
     * @see \Pecee\Model\Model::save()
     * @param array|null $data
     * @return static
     * @throws ModelException
     */
    public function save(array $data = null)
    {
        if (!is_array($this->columns)) {
            throw new ModelException('Columns property not defined.');
        }

        $updateData = [];
        foreach ($this->columns as $column) {
            $updateData[$column] = $this->{$column};
        }

        if ($data !== null) {
            $updateData = array_merge($updateData, $data);
        }

        $this->mergeRows($updateData);

        if ($this->exists()) {

            if ($this->timestamps) {
                $this->updated_at = Carbon::now()->toDateTimeString();
            }

            if (isset($updateData[$this->getPrimary()])) {
                // Remove primary key
                unset($updateData[$this->getPrimary()]);
            }

            $this->instance()->where($this->getPrimary(), '=', $this->{$this->getPrimary()})->update($updateData);
        } else {
            if ($this->{$this->primary} === null) {
                $this->{$this->primary} = $this->instance()->getQuery()->insert($updateData);
            } else {
                $this->instance()->getQuery()->insert($updateData);
            }
        }

        return $this;
    }

    public function delete()
    {
        if (!is_array($this->columns)) {
            throw new ModelException('Columns property not defined.');
        }

        if ($this->{$this->primary} != null) {
            $this->queryable->where($this->primary, '=', $this->{$this->primary});
        }

        $this->getQuery()->delete();
    }

    public function exists()
    {
        if ($this->{$this->primary} === null) {
            return false;
        }

        $id = $this->instance()->select([$this->primary])->where($this->primary, '=', $this->{$this->primary})->first();

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
        return ($this->hasRows() && isset($this->results['rows'][$key])) ? $this->results['rows'][$key] : null;
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
        return (isset($this->results['rows'])) ? $this->results['rows'] : [];
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
        return (isset($this->results['rows'][$name])) ? $this->results['rows'][$name] : null;
    }

    public function __set($name, $value)
    {
        $this->results['rows'][strtolower($name)] = $value;
    }

    public function getPrimary()
    {
        return $this->primary;
    }

    protected function parseArrayData($data)
    {
        if (is_array($data)) {
            $out = [];
            foreach ($data as $d) {
                $out[] = $this->parseArrayData($d);
            }

            return $out;
        }
        $data = (!is_array($data) && !mb_detect_encoding($data, 'UTF-8', true)) ? utf8_encode($data) : $data;

        return (Integer::isInteger($data)) ? intval($data) : $data;
    }

    protected function orderArrayRows(array &$rows)
    {
        uksort($rows, function ($a, $b) {
            return (array_search($a, $this->columns) > array_search($b, $this->columns));
        });
    }

    public function toArray()
    {

        $rows = $this->results['rows'];

        if ($rows && is_array($rows)) {
            foreach ($rows as $key => $row) {
                $key = (isset($this->rename[$key])) ? $this->rename[$key] : $key;
                if (in_array($key, $this->hidden)) {
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
                if (!method_exists($this, $method)) {
                    throw new ModelException('Missing required method ' . $method);
                }
                $output = call_user_func([$this, $method]);
                $with = (isset($this->rename[$with])) ? $this->rename[$with] : $with;
                $rows[$with] = ($output instanceof self || $output instanceof ModelCollection) ? $output->toArray() : $output;
            }

            return $rows;
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
     * @return static|null
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->queryable, $method)) {
            return call_user_func_array([$this->queryable, $method], $parameters);
        }

        return null;
    }

    /**
     * @param $method
     * @param $parameters
     * @return static
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        if (method_exists($instance->queryable, $method)) {
            return call_user_func_array([$instance, $method], $parameters);
        }

        return null;
    }

    public function __clone()
    {
        $this->queryable = clone $this->queryable;
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

}