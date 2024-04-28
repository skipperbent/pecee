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
    protected static bool $constraints = true;

    /**
     * @var Model
     */
    protected $related;

    /**
     * @var Model
     */
    protected $parent;
    protected $alias;
    protected $withDefault;

    /**
     * @var bool
     */
    protected bool $returnEmpty = false;

    public function __construct(Model $related, Model $parent)
    {
        $this->related = $related;
        $this->parent = $parent;
        $related->setOriginalRows($parent->getOriginalRows());

        $this->related->select([$this->related->getTable() . '.*']);

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
    public function getParent()
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
    public function withDefault($default): self
    {
        $this->withDefault = $default;

        return $this;
    }

    /**
     * When enabled the relation will not return a blank instance but allow null and empty values to be returned.
     *
     * @param bool $empty
     * @return static $this
     */
    public function withEmpty(bool $empty = true): self
    {
        $this->returnEmpty = true;

        return $this;
    }

    public function getDefaultFor(Model $parent)
    {
        if ($this->withDefault === null && $this->returnEmpty === true) {
            return null;
        }

        $instance = clone $parent;
        $instance->setOriginalRows($parent->getOriginalRows());

        if ($this->withDefault === null) {
            return $instance;
        }

        if (is_callable($this->withDefault) === true) {
            return call_user_func($this->withDefault, $instance) ?: $instance;
        }

        if (is_array($this->withDefault) === true) {
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
     * @return ModelRelation
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