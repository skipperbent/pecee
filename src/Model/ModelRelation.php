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
    protected bool $constraints = true;

    protected Model $related;
    protected Model $parent;
    protected ?string $alias = null;
    /**
     * @var array|\Closure|Model
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
     * @return static
     */
    public function alias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return Model
     */
    public function getParent(): Model
    {
        return $this->parent;
    }

    /**
     * Set default model that will be returned if relation is null.
     *
     * @param array|\Closure|Model $default
     * @return static
     */
    public function withDefault($default = []): self
    {
        $this->withDefault = $default;

        return $this;
    }

    /**
     * @param Model $parent
     * @return Model|ModelCollection
     */
    protected function getDefaultFor(Model $parent)
    {
        if ($this->withDefault === null) {
            return null;
        }

        if ($this->withDefault instanceof Model) {
            $parent->mergeRows($this->withDefault->getRows());

            return $parent;
        }

        if (is_callable($this->withDefault) === true) {
            return call_user_func($this->withDefault, $parent) ?: $parent;
        }

        if (is_array($this->withDefault) === true) {
            $parent->mergeRows($this->withDefault);
        }

        return $parent;
    }

    public function removeConstraints(): self
    {
        $this->related = $this->related->newQuery();
        $this->constraints = false;
        $this->addConstraints();

        return $this;
    }

    public function setConstraints(bool $enabled): self
    {
        $this->constraints = $enabled;

        return $this;
    }

    abstract public function addConstraints(): void;

    /**
     * @return Model|ModelCollection
     */
    abstract public function getResults();

    /**
     * @param string $name
     * @param array $arguments
     * @return static
     */
    public function __call(string $name, array $arguments = [])
    {
        $result = $this->related->{$name}(...$arguments);

        if ($result === $this->related) {
            return $this;
        }

        return $result;
    }

}