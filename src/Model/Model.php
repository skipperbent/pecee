<?php

namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Model\Relation\BelongsTo;
use Pecee\Model\Relation\BelongsToMany;
use Pecee\Model\Relation\HasMany;
use Pecee\Model\Relation\HasOne;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;
use Pecee\Str;

/**
 * @mixin ModelQueryBuilder
 */
abstract class Model implements \IteratorAggregate, \JsonSerializable
{
    protected $table;
    protected $results = ['rows' => [], 'original_rows' => []];
    protected $primary = 'id';
    protected $hidden = [];
    protected $with = [];
    protected $invokedElements = [];
    protected $rename = [];
    protected $columns = [];
    protected $timestamps = true;
    protected $fixedIdentifier = false;
    protected $relations = [];
    protected $filter = [];

    /**
     * @var ModelQueryBuilder
     */
    protected $queryable;

    public function __construct()
    {
        // Set table name if its not already defined
        if ($this->table === null) {
            $this->table = str_ireplace('model', '', class_basename(static::class));
        }

        $this->queryable = new ModelQueryBuilder($this);

        if ($this->timestamps === true) {
            $this->columns = array_merge($this->columns, [
                'updated_at',
                'created_at',
            ]);
        }
    }

    public function newQuery()
    {
        return new static();
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

    }

    public function onCollectionCreate($items)
    {
        return new ModelCollection($items);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param  string|null $related
     * @param  string|null $foreignKey
     * @param  string|null $ownerKey
     * @param  string|null $relation
     * @return BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {

        if ($relation === null) {
            $relation = $this->guessBelongsToRelation();
        }

        /* @var $instance Model */
        $instance = new $related();

        $foreignKey = $foreignKey ?: Str::camelize($relation) . '_' . $this->getPrimary();

        $ownerKey = $ownerKey ?: $instance->getPrimary();

        return new BelongsTo(
            $instance, $this, $foreignKey, $ownerKey, $relation
        );

    }

    /**
     * Define a many-to-many relationship.
     *
     * @param  string $related
     * @param  string|null $table
     * @param  string|null $foreignPivotKey
     * @param  string|null $relatedPivotKey
     * @param  string|null $parentKey
     * @param  string|null $relatedKey
     * @param  string|null $relation
     * @return BelongsToMany
     */
    public function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null)
    {

        if ($relation === null) {
            $relation = $this->guessBelongsToRelation();
        }

        /* @var $instance Model */
        $instance = new $related();

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        if ($table === null) {
            $table = $this->joiningTable($relation);
        }

        return new BelongsToMany(
            $instance, $this, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey ?: $this->getPrimary(),
            $relatedKey ?: $instance->getPrimary(), $relation
        );

    }

    /**
     * Define a one-to-one relationship.
     *
     * @param  string $related
     * @param  string|null $foreignKey
     * @param  string|null $localKey
     * @return HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {

        /* @var $instance Model */
        $instance = new $related();

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getPrimary();

        return new HasOne(
            $instance, $this, $instance->getTable() . '.' . $foreignKey, $localKey
        );
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param  string $related
     * @param  string|null $foreignKey
     * @param  string|null $localKey
     * @return HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        /* @var $instance Model */
        $instance = new $related();

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getPrimary();

        return new HasMany(
            $instance, $this, $instance->getTable() . '.' . $foreignKey, $localKey
        );
    }

    /**
     * Guess the "belongs to" relationship name.
     *
     * @return string
     */
    protected function guessBelongsToRelation()
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $caller[2]['function'];
    }

    /**
     * Get the joining table name for a many-to-many relation.
     *
     * @param  string $related
     * @return string
     */
    public function joiningTable($related)
    {
        // The joining table name, by convention, is simply the snake cased models
        // sorted alphabetically and concatenated with an underscore, so we can
        // just sort the models and join them together to get the table name.
        $models = [
            Str::camelize(class_basename($related)),
            Str::camelize(class_basename(static::class)),
        ];

        // Now that we have the model names in an array we can just sort them and
        // use the implode function to join them together with an underscores,
        // which is typically used by convention within the database system.
        sort($models);

        return strtolower(implode('_', $models));
    }

    public function getForeignKey()
    {
        return $this->table . '_' . $this->primary;
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

        if ($this->isNew() === false || $this->exists() === true) {

            if (isset($updateData[$this->getPrimary()]) === true) {
                // Remove primary key
                unset($updateData[$this->getPrimary()]);
            }

            if ($this->timestamps === true) {
                $updateData['updated_at'] = Carbon::now()->toDateTimeString();
            }

            $this->mergeRows($updateData);

            return static::instance()->where($this->getPrimary(), '=', $this->{$this->getPrimary()})->update($updateData);
        }

        $updateData = array_filter($updateData, function ($value) {
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        if ($this->timestamps === true && isset($updateData['created_at']) === false) {
            $updateData['created_at'] = Carbon::now()->toDateTimeString();
        }

        $this->mergeRows($updateData);

        if ($this->fixedIdentifier === false) {
            $this->{$this->primary} = static::instance()->getQuery()->insert($updateData);
        } else {
            static::instance()->getQuery()->insert($updateData);
        }

        $this->results['original_rows'][$this->primary] = $this->{$this->primary};

        return $this;
    }

    /**
     * @return \PDOStatement
     * @throws ModelException
     * @throws \Pecee\Pixie\Exception
     */
    public function delete()
    {
        if (is_array($this->columns) === false) {
            throw new ModelException('Columns property not defined.');
        }

        if ($this->isNew() === false) {
            $this->queryable->where($this->primary, '=', $this->{$this->primary});
        }

        return $this->queryable->getQuery()->delete();
    }

    /**
     * @return bool
     * @throws \Pecee\Pixie\Exception
     */
    public function exists()
    {
        if ($this->isNew() === true) {
            return false;
        }

        $id = static::instance()->select([$this->primary])->where($this->primary, '=', $this->{$this->primary})->first();

        if ($id !== null) {
            $this->{$this->primary} = $id->{$this->primary};

            return true;
        }

        return false;
    }

    public function isNew()
    {
        $originalRows = $this->getOriginalRows();

        return (isset($originalRows[$this->primary]) === false || $originalRows[$this->primary] === null);
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
        $this->invokeElement($name);

        return isset($this->results['rows'][$name]) ? $this->results['rows'][$name] : null;
    }

    public function __set($name, $value)
    {
        $this->results['rows'][strtolower($name)] = $value;
    }

    public function __isset($name)
    {
        $this->invokeElement($name);

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
            foreach ((array)$data as $key => $d) {
                $out[$key] = $this->parseArrayData($d);
            }

            return $out;
        }

        if (is_string($data) === true) {
            $encoding = mb_detect_encoding($data, 'UTF-8', true);
            $data = ($encoding === false || strtolower($encoding) !== 'utf-8') ? mb_convert_encoding($data, 'UTF-8', $encoding) : $data;
        }

        if (is_bool($data) === true) {
            return (bool)$data;
        }

        if (is_float($data) === true) {
            return (float)$data;
        }

        if (is_numeric($data) === true) {
            return (int)$data;
        }

        return $data;
    }

    protected function invokeElement($name)
    {

        if (isset($this->with[$name]) === false || in_array($name, $this->invokedElements, true) === true) {
            return;
        }

        $this->invokedElements[] = $name;
        $with = $this->with[$name];

        if (is_numeric($name) === true) {
            $name = $with;
        }

        if ($with instanceof \Closure) {
            $output = $with($this);
        } else {
            $method = Str::camelize($name);
            $output = $this->$method();
        }

        if ($output instanceof Model || $output instanceof ModelCollection) {
            $output = $output->toArray();
        } elseif ($output instanceof ModelRelation) {
            $output = $output->getResults()->toArray();
        }

        $name = isset($this->rename[$name]) ? $this->rename[$name] : $name;

        $this->{$name} = $output;
    }

    /**
     * @param array|string|null $filter
     * @return array
     */
    public function toArray(array $filter = [])
    {
        $this->filter = array_merge($this->filter, $filter);

        foreach (array_keys($this->with) as $key) {
            $this->invokeElement($key);
        }

        $rows = $this->getRows();

        $output = [];

        foreach ($rows as $key => $row) {
            $key = isset($this->rename[$key]) ? $this->rename[$key] : $key;
            if (in_array($key, $this->hidden, true) === false) {
                $output[$key] = $this->parseArrayData($row);
            }
        }

        if (count($this->filter) > 0) {

            $filtered = [];

            foreach ($this->filter as $key) {
                if (isset($output[$key]) === true) {
                    $filtered[$key] = $output[$key];
                }
            }

            $output = $filtered;
        }

        return $output;
    }

    /**
     * Add data to output
     * @param string|array $method
     * @return static $this
     */
    public function with($method)
    {
        $this->with = array_merge($this->with, (array)$method);

        return $this;
    }

    /**
     * Remove output data
     *
     * @param string|array $method
     * @return static $this
     */
    public function without($method)
    {
        if (is_array($method) === true) {
            foreach ($method as $with) {
                $key = array_search($with, $this->with, true);
                if ($key !== false) {
                    unset($this->with[$key]);
                }
            }

            return $this;
        }

        unset($this->with[array_search($method, $this->with, true)]);

        return $this;
    }

    public function getWith()
    {
        return $this->with;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return QueryBuilderHandler|null
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->queryable, $method) === true) {
            return $this->queryable->{$method}(...$parameters);
        }

        return null;
    }

    /**
     * Call static
     * @param string $method
     * @param array $parameters
     *
     * @return static|QueryBuilderHandler|null
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * Set original rows
     * @param array $rows
     */
    public function setOriginalRows(array $rows)
    {
        $this->results['original_rows'] = $rows;
    }

    public function getOriginalRows()
    {
        return $this->results['original_rows'];
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

    public function getAttribute($name)
    {
        return $this->{$name};
    }

    public function setAttribute($name, $value)
    {
        $this->{$name} = $value;
    }

    /**
     * Hide fields
     * @param array $fields
     * @return $this
     */
    public function hideFields(array $fields)
    {
        $this->hidden = array_merge($this->hidden, $fields);

        return $this;
    }

    /**
     * Get hidden fields
     *
     * @return array
     */
    public function getHiddenFields()
    {
        return $this->hidden;
    }

    /**
     * Filter fields
     *
     * @param array $fields
     * @return static $this
     */
    public function filter(array $fields)
    {
        $this->filter = array_merge($this->filter, $fields);

        return $this;
    }

    /**
     * Get filtered fields
     *
     * @return array
     */
    public function getFilteredFields()
    {
        return $this->filter;
    }

    /**
     * Rename fields
     *
     * @param array $fields
     * @return static $this
     */
    public function rename(array $fields)
    {
        $this->rename = array_merge($this->rename, $fields);

        return $this;
    }

    /**
     * Get renamed fields
     *
     * @return array
     */
    public function getRenamedFields()
    {
        return $this->rename;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

}