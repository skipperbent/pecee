<?php

namespace Pecee\Model;

use ArrayIterator;
use Carbon\Carbon;
use Closure;
use IteratorAggregate;
use JsonSerializable;
use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Model\Relation\BelongsTo;
use Pecee\Model\Relation\BelongsToMany;
use Pecee\Model\Relation\HasMany;
use Pecee\Model\Relation\HasOne;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;
use Pecee\Str;
use ReflectionClass;
use ReflectionException;
use stdClass;

/**
 * @mixin ModelQueryBuilder
 */
abstract class Model implements IteratorAggregate, JsonSerializable
{
    protected string $table = '';
    protected array $results = ['rows' => [], 'original_rows' => []];
    protected string $primaryKey = 'id';
    protected array $hidden = [];
    protected array $with = [];
    protected array $without = [];
    protected array $withAutoInvokeColumns = [];
    protected array $invokedElements = [];
    protected array $rename = [];
    protected array $columns = [];
    protected array $updateColumns = [];
    protected array $fieldTypes = [];
    protected bool $timestamps = true;
    protected bool $fixedIdentifier = false;
    protected array $filter = [];
    protected ModelQueryBuilder $queryable;

    public const COLUMN_TYPE_INT = 'int';
    public const COLUMN_TYPE_BOOL = 'bool';
    public const COLUMN_TYPE_FLOAT = 'float';

    public function __construct()
    {
        // Set table name if its not already defined
        if ($this->table === '') {
            $this->table = str_ireplace('model_', '', class_basename(static::class));
        }

        $this->updateColumns = $this->columns;

        $this->queryable = new ModelQueryBuilder($this);

        if ($this->timestamps === true) {
            $this->columns = array_merge($this->columns, [
                'updated_at',
                'created_at',
            ]);
        }
    }

    public function newQuery(): self
    {
        return new static();
    }

    /**
     * Create new instance.
     *
     * @return static
     */
    public static function instance(): self
    {
        return new static();
    }

    /**
     * @param stdClass $data
     * @return static $this
     * @throws \Pecee\Pixie\Exception
     */
    public function onNewInstance(stdClass $data): self
    {
        // Clone properties but reset query-builder
        $instance = clone $this;
        $instance->setQuery(new ModelQueryBuilder($instance));

        return $instance;
    }

    public function onInstanceCreate(): void
    {
        if ($this->isNew() === true) {
            return;
        }

        foreach ($this->withAutoInvokeColumns as $column) {
            $this->invokeElement($column);
        }
    }

    public function onCollectionCreate($items): ModelCollection
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
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null, ?string $relation = null): BelongsTo
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
    public function belongsToMany(string $related, ?string $table = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null, ?string $parentKey = null, ?string $relatedKey = null, ?string $relation = null): BelongsToMany
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
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        /* @var $instance Model */
        $instance = new $related();

        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->getPrimaryKey();

        return new HasOne(
            $instance, $this, "{$instance->getTable()}.$foreignKey", $localKey
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
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
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
    protected function guessBelongsToRelation(): string
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
    public function joiningTable(string $related): string
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

    public function getForeignKey(): string
    {
        return $this->table . '_' . $this->primaryKey;
    }

    /**
     * Save item
     * @param array $data
     * @return static
     * @throws ModelException|\Pecee\Pixie\Exception
     */
    public function save(array $data = []): self
    {
        $updateData = [];
        foreach ($this->updateColumns as $column) {
            $updateData[$column] = $this->{$column};
        }

        $originalRows = $this->getOriginalRows();

        /* Only save valid columns */
        $columns = $this->columns;
        $data = array_filter($data, static function ($key) use ($columns) {
            return (in_array($key, $columns, true) === true);
        }, ARRAY_FILTER_USE_KEY);

        $updateData = array_merge($updateData, $data);

        foreach ($updateData as $key => $value) {
            if (array_key_exists($key, $originalRows) === true && $originalRows[$key] === $value) {
                unset($updateData[$key]);
            }
        }

        if (count($updateData) === 0) {
            return $this;
        }

        if ($this->isNew() === false || $this->exists() === true) {

            if (isset($updateData[$this->getPrimaryKey()]) === true) {
                // Remove primary key
                unset($updateData[$this->getPrimaryKey()]);
            }

            if ($this->timestamps === true) {
                $updateData['updated_at'] = Carbon::now(app()->getTimezone())->toDateTimeString();
            }

            $this->mergeRows($updateData);

            return static::instance()->where($this->getPrimaryKey(), '=', $this->{$this->getPrimaryKey()})->update($updateData);
        }

        $updateData = array_filter($updateData, static function ($value) {
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        if ($this->timestamps === true && isset($updateData['created_at']) === false) {
            $updateData['created_at'] = Carbon::now(app()->getTimezone())->toDateTimeString();
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
     * @return \PDOStatement|static|null
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
    public function exists(): bool
    {
        if ($this->isNew() === true) {
            return false;
        }

        $id = static::instance()->select([$this->primaryKey])->where($this->primaryKey, '=', $this->{$this->primaryKey})->first();

        if ($id !== null) {
            $this->{$this->primaryKey} = $id->{$this->primaryKey};

            return true;
        }

        return false;
    }

    public function isNew(): bool
    {
        $originalRows = $this->getOriginalRows();

        return (isset($originalRows[$this->primaryKey]) === false || $originalRows[$this->primaryKey] === null);
    }

    public function hasRows(): bool
    {
        return (isset($this->results['rows']) && count($this->results['rows']) > 0);
    }

    /**
     * Get row
     * @param int $key
     * @return mixed
     */
    public function getRow(int $key)
    {
        return ($this->hasRows() === true && isset($this->results['rows'][$key])) ? $this->results['rows'][$key] : null;
    }

    public function setRow(string $key, $value): void
    {
        $this->results['rows'][$key] = $value;
    }

    public function setRows(array $rows): void
    {
        $this->results['rows'] = $rows;
    }

    public function mergeRows(array $rows): void
    {
        $this->results['rows'] = array_merge($this->results['rows'], $rows);
    }

    public function mergeData(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * Get rows
     * @return array
     */
    public function getRows(): array
    {
        return $this->results['rows'] ?? [];
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getResults(): array
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

        return $this->parseFieldType($name, $this->results['rows'][$name] ?? null);
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

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    protected function parseArrayData($data)
    {
        if ($data instanceof self || $data instanceof ModelCollection) {
            return $data->toArray();
        }

        if ($data instanceof ModelRelation) {
            return $data->getResults()->toArray();
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

    protected function invokeMethod(string $name, ?string $key = null): bool
    {
        try {
            $reflection = new ReflectionClass($this);
            $method = $reflection->getMethod($name);

            if ($method->getNumberOfRequiredParameters() === 0) {
                $output = $this->{$name}();
                if ($output instanceof ModelRelation) {
                    $output = $output->getResults();
                }

                $key = $key ?? $name;

                if (stripos($key, 'get') === 0) {
                    $key = trim(substr($key, 3), '_');
                }

                $this->results['rows'][$this->rename[$name] ?? $key] = $output;

                return true;
            }
        } catch (ReflectionException $e) {

        }

        return false;
    }

    protected function invokeElement(string $name): void
    {
        if (in_array($name, $this->invokedElements, true) === true) {
            return;
        }

        $this->invokedElements[] = $name;

        if (isset($this->with[$name]) === false && $this->invokeMethod($name)) {
            return;
        }

        if (isset($this->with[$name]) === false) {
            return;
        }

        $with = $this->with[$name];

        if (is_numeric($name) === true) {
            $name = $with;
        }

        $output = $with;

        if ($with instanceof Closure) {
            $output = $with($this);
        } else {
            if ($this->invokeMethod($name) === true) {
                return;
            }

            if ($this->invokeMethod('get' . ucfirst($name), $name)) {
                return;
            }

            if ($this->invokeMethod($with)) {
                return;
            }

            if ($this->{$with} !== null) {
                $output = $this->{$with};
            }
        }

        $this->results['rows'][$this->rename[$name] ?? $name] = $output;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function toArray(array $filter = []): array
    {
        $this->filter = array_merge($this->filter, $filter);

        foreach (array_keys($this->with) as $key) {
            $this->invokeElement($key);
        }

        $output = [];

        // Ensure default columns are always present
        foreach ($this->columns as $column) {

            $key = $this->rename[$column] ?? $column;

            if (in_array($column, $this->hidden, true) === false) {
                $output[$key] = $this->{$column};
            }
        }

        foreach ($this->getRows() as $key => $row) {

            $keyFormatted = Str::deCamelize($key);
            if (in_array($key, $this->without, true) === true || in_array($keyFormatted, $this->without, true) === true) {
                continue;
            }

            $key = $this->rename[$key] ?? $keyFormatted;

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
     * @return static
     */
    public function with($method): self
    {
        foreach ((array)$method as $key => $value) {
            if (is_string($value) === true && is_numeric($key) === true) {
                $this->with[$value] = $value;

                // Remove without
                if (isset($this->without[$value])) {
                    unset($this->without[$value]);
                }
            } else {
                $this->with[$key] = $value;

                // Remove without
                if (isset($this->without[$key])) {
                    unset($this->without[$key]);
                }
            }
        }

        return $this;
    }

    public function withAutoInvoke(array $columns): self
    {
        $this->withAutoInvokeColumns = $columns;

        return $this;
    }

    /**
     * Remove output data
     *
     * @param string|array $method
     * @return static
     */
    public function without($method): self
    {
        if (is_array($method) === true) {
            foreach ($method as $with) {
                $this->without[] = $with;
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

    /**
     * Parse field type
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    protected function parseFieldType(string $name, $value)
    {
        if (isset($this->fieldTypes[$name]) === true) {
            $type = $this->fieldTypes[$name];
            if ($type instanceof Closure) {
                return $type($value);
            }

            switch ($type) {
                case static::COLUMN_TYPE_BOOL:
                    return (bool)$value;
                case static::COLUMN_TYPE_FLOAT:
                    return (float)$value;
                case static::COLUMN_TYPE_INT:
                    return (int)$value;
            }
        }

        return $value;
    }

    public function getWith(): array
    {
        return $this->with;
    }

    public function getWithout(): array
    {
        return $this->without;
    }

    public function setWithout(array $without): self
    {
        $this->without = $without;

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return static|QueryBuilderHandler|null
     */
    public function __call(string $method, $parameters)
    {
        if (method_exists($this->queryable, $method) === true) {
            return $this->queryable->{$method}(...$parameters);
        }

        return null;
    }

    /**
     * Call static
     * @param string $method
     * @param ... $parameters
     *
     * @return static|QueryBuilderHandler|null
     */
    public static function __callStatic(string $method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * Set original rows
     * @param array $rows
     */
    public function setOriginalRows(array $rows): void
    {
        $this->results['original_rows'] = $rows;
    }

    public function getOriginalRows(bool $currentData = false): array
    {
        if ($currentData === true) {
            $rows = [];
            foreach ($this->getColumns() as $column) {
                $rows[$column] = $this->{$column};
            }

            return $rows;
        }

        return $this->results['original_rows'];
    }

    /**
     * Automaticially update timestamps
     * @return bool
     */
    public function getUpdateTimestamps(): bool
    {
        return $this->timestamps;
    }

    /**
     * Automaticially update timestamps
     * @param bool $value
     * @return static $this
     */
    public function setUpdateTimestamps(bool $value): self
    {
        $this->timestamps = $value;

        return $this;
    }

    public function setQuery(ModelQueryBuilder $query): void
    {
        $this->queryable = $query;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return ArrayIterator
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getRows());
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

    public function setAttribute($name, $value): self
    {
        $this->{$name} = $value;

        return $this;
    }

    /**
     * Hide fields
     * @param array $fields
     * @return $this
     */
    public function hideFields(array $fields): self
    {
        $this->hidden = array_merge($this->hidden, $fields);

        return $this;
    }

    /**
     * Get hidden fields
     *
     * @return array
     */
    public function getHiddenFields(): array
    {
        return $this->hidden;
    }

    /**
     * Filter fields
     *
     * @param array $fields
     * @return static $this
     */
    public function filter(array $fields): self
    {
        $this->filter = array_merge($this->filter, $fields);

        return $this;
    }

    /**
     * Get filtered fields
     *
     * @return array
     */
    public function getFilteredFields(): array
    {
        return $this->filter;
    }

    /**
     * Rename fields
     *
     * @param array $fields
     * @return static $this
     */
    public function rename(array $fields): self
    {
        $this->rename = array_merge($this->rename, $fields);

        return $this;
    }

    public function overwriteQuery(bool $enabled): self
    {
        $this->getQuery()->setOverwriteEnabled($enabled);

        return $this;
    }

    /**
     * Get renamed fields
     *
     * @return array
     */
    public function getRenamedFields(): array
    {
        return $this->rename;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getUpdateColumns(): array
    {
        return $this->updateColumns;
    }

    public function setUpdateColumns(array $columns): self
    {
        $this->updateColumns = $columns;

        return $this;
    }

    public function __toString()
    {
        return (string)$this->{$this->getPrimaryKey()};
    }

}