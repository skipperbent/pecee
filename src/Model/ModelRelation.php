<?php

namespace Pecee\Model;

use Pecee\Model\Collections\ModelCollection;

/**
 * @mixin Model
 */
abstract class ModelRelation
{

    /**
     * Indicates if the relation is adding constraints.
     *
     * @var bool
     */
    protected static $constraints = true;

    /**
     * @var Model
     */
    protected $related;

    /**
     * @var Model
     */
    protected $parent;

    /**
     * @var string|null
     */
    protected $alias;

    /**
     * @var array|\Closure
     */
    protected $withDefault;

    public function __construct(Model $related, Model $parent)
    {
        $this->related = $related;
        $this->parent = $parent;

        $this->addConstraints();
    }

    /**
     * Set alias
     *
     * @param string $alias
     * @return static $this
     */
    public function alias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return Model
     */
    public function getParent() : Model
    {
        return $this->parent;
    }

    protected $defaultModel;

    /**
     * Set default model that will be returned if relation is null.
     *
     * @param array|\Closure $default
     * @return static $this
     */
    public function withDefault($default) : self
    {
        $this->withDefault = $default;

        return $this;
    }

    /**
     * @param Model $parent
     * @return Model
     * @throws \Pecee\Pixie\Exception
     */
    public function getDefaultFor(Model $parent) : Model
    {
        $instance = $parent->newQuery();

        if ($this->withDefault === null) {
            return $instance;
        }

        if (\is_callable($this->withDefault) === true) {
            return \call_user_func($this->withDefault, $instance) ?: $instance;
        }

        if (\is_array($this->withDefault) === true) {
            $instance->mergeRows($this->withDefault);
        }

        return $instance;
    }

    abstract public function addConstraints();

    /**
     * @return Model|ModelCollection
     */
    abstract public function getResults();

    /**
     * @param string $name
     * @param array $arguments
     * @return Model
     */
    public function __call($name, $arguments)
    {
        $result = $this->related->{$name}(...$arguments);

        if ($result === $this->related) {
            return $this;
        }

        return $result;
    }

}