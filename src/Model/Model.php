<?php
namespace Pecee\Model;

use Pecee\Debug;
use Pecee\Integer;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Str;
use Pixie\QueryBuilder\QueryBuilderHandler;

/**
 *
 * Helper docs to support both static and non-static calls, which redirects to ModelQueryBuilder.
 *
 * @method static Model limit(int $id)
 * @method static Model skip(int $id)
 * @method static Model take(int $id)
 * @method static Model offset(int $id)
 * @method static Model where(string $key, string $operator = null, string $value = null)
 * @method static Model whereIn(string $key, array $values)
 * @method static Model whereNot(string $key, string $operator = null, string $value = null)
 * @method static Model whereNotIn(string $key, array $values)
 * @method static Model whereNull(string $key)
 * @method static Model whereNotNull(string $key)
 * @method static Model whereBetween(string $key, string $valueFrom, string $valueTo)
 * @method static Model orWhere(string $key, string $operator = null, string $value = null)
 * @method static Model orWhereIn(string $key, array $values)
 * @method static Model orWhereNotIn(string $key, array $values)
 * @method static Model orWhereNot(string $key, string $operator = null, string $value = null)
 * @method static Model orWhereNull(string $key)
 * @method static Model orWhereNotNull(string $key)
 * @method static Model orWhereBetween(string $key, string $valueFrom, string $valueTo)
 * @method static \Pecee\Model\ModelCollection get()
 * @method static \Pecee\Model\ModelCollection all()
 * @method static Model find(string $id)
 * @method static Model findOrfail(string $id)
 * @method static Model first()
 * @method static Model firstOrFail()
 * @method static Model count()
 * @method static Model max(string $field)
 * @method static Model sum(string $field)
 * @method static Model update(array $data)
 * @method static Model create(array $data)
 * @method static Model firstOrCreate(array $data)
 * @method static Model firstOrNew(array $data)
 * @method static Model destroy(array $ids)
 * @method static Model select(array $fields)
 * @method static Model groupBy(string $field)
 * @method static Model orderBy(string $field, string $defaultDirection = 'ASC')
 * @method static Model join(string $table, string $key, string $operator = null, string $value = null, string $type = 'inner'))
 * @method static QueryBuilderHandler getQuery()
 * @method static string raw(string $value, array $bindings = array())
 * @method static string subQuery(QueryBuilderHandler $queryBuilder, string $alias = null)
 */

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
     * @var ModelQueryBuilder
     */
    protected $queryable;

    public function __construct() {

        // Set table name if its not already defined
        if($this->table === null) {
            $name = explode('\\', get_called_class());
            $name = str_ireplace('model', '', end($name));
            $this->table = strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $name));
        }

        $this->queryable = $this->newQuery();

        if(env('DEBUG')) {

            $this->queryable->getQuery()->registerEvent('before-*', $this->table,
                function (QueryBuilderHandler $qb) {
                    Debug::getInstance()->add('START QUERY: ' . $qb->getQuery()->getRawSql());
                });

            $this->queryable->getQuery()->registerEvent('after-*', $this->table,
                function (QueryBuilderHandler $qb) {
                    Debug::getInstance()->add('END QUERY: ' . $qb->getQuery()->getRawSql());
                });
        }

        $this->results = array('rows' => array());
    }

    public function newQuery($table = null) {
        return new ModelQueryBuilder($this, (($table === null) ? $this->table : $table));
    }

    public static function instance() {
        return new static();
    }

    public function onInstanceCreate() {
        if (count($this->join)) {
            foreach ($this->join as $join) {
                $method = Str::camelize($join);
                $this->{$join} = $this->$method();
            }
        }
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
            $this->update($data);
        } else {
            $this->{$this->primary} = $this->instance()->getQuery()->insert($data);
        }

        return $this;
    }

    public function delete() {
        if(!is_array($this->columns)) {
            throw new ModelException('Columns property not defined.');
        }

        if($this->{$this->primary} != null) {
            $this->queryable->where($this->primary, '=', $this->{$this->primary});
        }

        $this->getQuery()->delete();
    }

    public function exists() {
        if($this->{$this->primary} === null) {
            return false;
        }

        $id = $this->instance()->select([$this->primary])->where($this->primary, '=', $this->{$this->primary})->first();

        if($id !== null) {
            $this->{$this->primary} = $id->{$this->primary};
            return true;
        }

        return false;
    }

    public function hasRows() {
        return (isset($this->results['rows']) && count($this->results['rows']) > 0);
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
    }

    public function mergeRows(array $rows) {
        $this->results['rows'] = array_merge($this->results['rows'], $rows);
    }

    /**
     * Get rows
     * @return array
     */
    public function getRows() {
        return (isset($this->results['rows'])) ? $this->results['rows'] : array();
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
                if(in_array($key, $this->hidden)) {
                    unset($rows[$key]);
                    continue;
                }
                $rows[$key] = $this->parseArrayData($row);
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
                $rows[$with] = ($output instanceof self) ? $output->toArray() : $output;
            }
            return $rows;
        }

        return $rows;
    }

    public function getColumns() {
        return $this->columns;
    }

    /**
     * @param $method
     * @param $parameters
     * @return static|null
     */
    public function __call($method, $parameters) {
        $query = $this->queryable;
        if(method_exists($query, $method)) {
            return call_user_func_array([$query, $method], $parameters);
        }

        return null;
    }

    /**
     * @param $method
     * @param $parameters
     * @return static|null
     */
    public static function __callStatic($method, $parameters) {
        $instance = new static;

        if(method_exists($instance->queryable, $method)) {
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
    public function getIterator() {
        return new \ArrayIterator($this->getRows());
    }

}