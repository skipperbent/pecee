<?php

namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\ArrayUtil;
use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Model\Relation\BelongsTo;
use Pecee\Model\Relation\BelongsToMany;
use Pecee\Model\Relation\HasMany;
use Pecee\Model\Relation\HasOne;
use Pecee\Pixie\Connection;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;
use Pecee\Str;

/**
 * @mixin ModelQueryBuilder
 */
abstract class Model implements \IteratorAggregate, \JsonSerializable, \Serializable
{
    protected $table;
    protected $results = ['rows' => [], 'original_rows' => []];
    protected $primaryKey = 'id';
    protected $hidden = [];
    protected $with = [];
    protected $without = [];
    protected $withAutoInvokeColumns = [];
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
    protected ModelQueryBuilder $queryable;

    public function __construct()
    {
        // Set table name if its not already defined
        if ($this->table === null) {
            $this->table = str_ireplace('model', '', class_basename(static::class));
        }

        $this->queryable = new ModelQueryBuilder($this, $this->onConnectionCreate());

        if ($this->timestamps === true) {
            $this->columns = array_merge($this->columns, [
                'updated_at',
                'created_at',
            ]);
        }
    }

    protected function onConnectionCreate(): ?Connection
    {
        return app()->getConnection();
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

    /**
     * @param \stdClass $data
     * @param bool $resetQuery
     * @return static $this
     * @throws \Pecee\Pixie\Exception
     */
    public function onNewInstance(\stdClass $data, bool $resetQuery = false): self
    {
        $item = clone $this;
        if ($resetQuery) {
            $item->setQuery(new ModelQueryBuilder($this, $this->onConnectionCreate()));
        }

        return $item;
    }

    public function onInstanceCreate()
    {
        if ($this->isNew() === true) {
            return;
        }

        foreach ($this->withAutoInvokeColumns as $column) {
            $this->invokeElement($column);
        }
    }

    public function onCollectionCreate($items)
    {
        return new ModelCollection($items);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param string|null $related
     * @param string|null $foreignKey
     * @param string|null $ownerKey
     * @param string|null $relation
     * @return BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {

        if ($relation === null) {
            $relation = $this->guessBelongsToRelation();
        }

        /* @var $instance Model */
        $instance = new $related();

        $foreignKey = $foreignKey ?: Str::camelize($relation) . '_' . $this->getPrimaryKey();

        $ownerKey = $ownerKey ?: $instance->getPrimaryKey();

        return new BelongsTo(
            $instance, $this, $foreignKey, $ownerKey, $relation
        );

    }

    /**
     * Define a many-to-many relationship.
     *
     * @param string $related
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @param string|null $relation
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
            $relatedPivotKey, $parentKey ?: $this->getPrimaryKey(),
            $relatedKey ?: $instance->getPrimaryKey(), $relation
        );

    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        /* @var $instance Model */
        $instance = new $related();

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getPrimaryKey();

        return new HasOne(
            $instance, $this, $instance->getTable() . '.' . $foreignKey, $localKey
        );
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        /* @var $instance Model */
        $instance = new $related();

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getPrimaryKey();

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
     * @param string $related
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
        return $this->table . '_' . $this->primaryKey;
    }

    /**
     * Save item
     * @param array|null $data
     * @return static
     * @throws ModelException|\Pecee\Pixie\Exception
     * @see \Pecee\Model\Model::save()
     */
    public function save(array $data = null)
    {
        if (is_array($this->columns) === false) {
            throw new ModelException('Columns property not defined.');
        }

        $updateData = [];
        foreach ($this->columns as $column) {
            if ($data !== null && $this->{$column} !== null || $data === null) {
                $updateData[$column] = $this->{$column};
            }
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

        if ($this->isNew() === false) {

            if (isset($updateData[$this->getPrimaryKey()]) === true) {
                // Remove primary key
                unset($updateData[$this->getPrimaryKey()]);
            }

            if ($this->timestamps === true) {
                $updateData['updated_at'] = Carbon::now()->toDateTimeString();
            }

            $this->mergeRows($updateData);

            return static::instance()->where($this->getPrimaryKey(), '=', $this->{$this->getPrimaryKey()})->update($updateData);
        }

        $updateData = array_filter($updateData, function ($value) {
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        if ($this->timestamps === true && isset($updateData['created_at']) === false) {
            $updateData['created_at'] = Carbon::now()->toDateTimeString();
        }

        $this->mergeRows($updateData);

        if ($this->fixedIdentifier === false) {
            $this->{$this->primaryKey} = static::instance()->getQuery()->insert($updateData);
        } else {
            static::instance()->getQuery()->insert($updateData);
        }

        $this->results['original_rows'][$this->primaryKey] = $this->{$this->primaryKey};

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
            $this->queryable->where($this->primaryKey, '=', $this->{$this->primaryKey});
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
            return ($this->count() > 0);
        }

        $id = static::instance()->select([$this->primaryKey])->where($this->primaryKey, '=', $this->{$this->primaryKey})->first();

        if ($id !== null) {
            $this->{$this->primaryKey} = $id->{$this->primaryKey};

            return true;
        }

        return false;
    }

    public function isNew()
    {
        $originalRows = $this->getOriginalRows();

        return (isset($originalRows[$this->primaryKey]) === false || $originalRows[$this->primaryKey] === null);
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

    public function mergeData(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
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

        return $this->results['rows'][$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->results['rows'][$name] = $value;
    }

    public function __isset($name)
    {
        $this->invokeElement($name);

        return isset($this->results['rows'][$name]) || in_array($name, $this->columns, true);
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    protected function parseArrayData($data)
    {
        if ($data instanceof self || $data instanceof ModelCollection) {
            return $data->toArray();
        }

        if ($data instanceof ModelRelation) {
            return ($data->getResults() !== null) ? $data->getResults()->toArray() : null;
        }

        if (is_array($data) === true) {
            $out = [];
            foreach ((array)$data as $key => $d) {
                $out[$key] = $this->parseArrayData($d);
            }

            return $out;
        }

        if (is_string($data) === true) {
            $encoding = mb_detect_encoding($data, 'UTF-8', true);
            $data = ($encoding === false || strtolower($encoding) !== 'utf-8') ? mb_convert_encoding($data, 'UTF-8', ($encoding === false) ? null : $encoding) : $data;
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

    protected function invokeElement($name): void
    {
        if (in_array($name, $this->invokedElements, true) === true) {
            return;
        }

        if (in_array($name, $this->without, true) === true) {
            return;
        }

        $this->invokedElements[] = $name;

        $output = null;
        $isInvoked = false;

        if (isset($this->with[$name])) {
            $with = $this->with[$name];

            if (is_numeric($name) === true) {
                $name = $with;
            }

            $output = $with;

            if ($with instanceof \Closure) {
                $output = $with($this);
                $isInvoked = true;
            }
        }

        if ($isInvoked === false && is_string($name) && !isset($this->relations[$name]) && method_exists($this, $name)) {

            $this->relations[$name] = true;

            try {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod($name);

                if ($method->getNumberOfParameters() === 0) {
                    $output = $this->{$name}();
                    if ($output instanceof ModelRelation) {
                        $this->results['rows'][$name] = $output->getResults();
                        return;
                    }
                }
            } catch (\ReflectionException $e) {

            }
        }

        if (isset($this->with[$name]) === false) {
            return;
        }

        if (($with instanceof \Closure) === false) {
            if (method_exists($this, $name)) {
                // If method exist call method and remove getter
                $output = $this->{$name}();
                $name = str_ireplace('get', '', $name);
            } else if (method_exists($this, $with)) {
                // If with value contains method name
                $output = $this->{$with}();
            }
        }

        $this->results['rows'][$name] = $output;
    }

    /**
     * @param array|string|null $filter
     * @return array
     */
    public function toArray(array $filter = []): array
    {
        $filter = (count($filter) > 0) ? $filter : $this->filter;

        foreach (array_keys($this->with) as $key) {
            if (count($filter) === 0 || in_array($key, $filter, true) || in_array($key, $filter, true) && isset($this->rename[$key])) {
                $this->invokeElement($key);
            }
        }

        $output = [];

        // Ensure default columns are always present
        foreach ($this->columns as $column) {
            // Skip without
            if (in_array($column, $this->without, true) === true && in_array($column, $filter, true) === false || count($filter) > 0 && in_array($column, $filter, true) === false) {
                continue;
            }

            $column = $this->rename[$column] ?? $column;
            $output[$column] = $this->{$column};
        }

        foreach ($this->getRows() as $key => $row) {

            $key = $this->rename[$key] ?? $key;

            // Skip without
            if (in_array($key, $this->without, true) === true || isset($this->rename[$key]) || count($filter) > 0 && in_array(Str::deCamelize($key), $filter, true) === false) {
                continue;
            }

            if (in_array($key, $this->hidden, true) === false) {

                // Check if local method exist
                if (method_exists($this, 'get' . ucfirst($key)) === true) {
                    $row = call_user_func([$this, 'get' . ucfirst($key)]);
                }

                $output[Str::deCamelize($key)] = $this->parseArrayData($row);
            }
        }

        foreach ($this->rename as $old => $new) {
            $output[$new] = $output[$old] ?? $this->{$old};
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
        foreach ((array)$method as $key => $value) {
            if (is_string($value) === true && is_numeric($key) === true) {
                $this->with[$value] = $value;
                $invokedKey = array_search($value, $this->invokedElements, true);
            } else {
                $this->with[$key] = $value;
                $invokedKey = array_search($key, $this->invokedElements, true);
            }

            if ($invokedKey !== false) {
                unset($this->invokedElements[$invokedKey]);
            }
        }

        return $this;
    }

    public function withAutoInvoke(array $columns)
    {
        $this->withAutoInvokeColumns = $columns;

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
            $this->without = array_merge($this->without, $method);
            foreach ($method as $with) {
                $key = array_search($with, $this->with, true);
                if ($key !== false) {
                    unset($this->with[$key]);
                }
            }

            return $this;
        }

        $this->without[] = $method;

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

    /**
     * Automaticially update timestamps
     * @return bool
     */
    public function getUpdateTimestamps()
    {
        return $this->timestamps;
    }

    /**
     * Automaticially update timestamps
     * @param bool $value
     * @return static $this
     */
    public function setUpdateTimestamps($value)
    {
        $this->timestamps = $value;

        return $this;
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
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->getRows());
    }

    public function __clone()
    {
        $this->queryable = clone $this->queryable;
        $this->queryable->setModel($this);
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
     * @param bool $merge
     * @return static $this
     */
    public function filter(array $fields, bool $merge = false): self
    {
        if ($merge === true) {
            $this->filter = array_merge($this->filter, $fields);
        } else {
            $this->filter = $fields;
        }

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
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function __toString()
    {
        return (string)$this->{$this->getPrimaryKey()};
    }

    public function __wakeup()
    {
        $this->queryable = new ModelQueryBuilder($this, $this->onConnectionCreate());
    }

    public function serialize()
    {
        return [
            'results' => $this->results,
            'with' => ArrayUtil::serialize($this->with),
            'without' => $this->without,
            'rename' => $this->rename,
            'hidden' => $this->hidden,
            'filter' => $this->filter,
            'invokedElements' => $this->invokedElements,
            'relations' => $this->relations,
        ];
    }

    /**
     * @param array $data
     */
    public function unserialize($data)
    {
        $this->results = $data['results'];
        $this->with = ArrayUtil::unserialize($data['with']);
        $this->without = $data['without'];
        $this->rename = $data['rename'];
        $this->hidden = $data['hidden'];
        $this->filter = $data['filter'];
        $this->invokedElements = $data['invokedElements'];
        $this->relations = $data['relations'];
        $this->__wakeup();
    }

    public function __serialize(): array
    {
        return $this->serialize();
    }

    public function __unserialize(array $data): void
    {
        $this->unserialize($data);
    }

}