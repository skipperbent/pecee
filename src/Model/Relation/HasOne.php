<?php

namespace Pecee\Model\Relation;

use Pecee\Model\Model;
use Pecee\Model\ModelRelation;

class HasOne extends ModelRelation
{

    protected string $localKey;
    protected string $foreignKey;

    public function __construct(Model $related, Model $parent, $foreignKey, $localKey)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;

        parent::__construct($related, $parent);
    }

    public function addConstraints(): void
    {
        if (static::$constraints === true) {
            $this->related
                ->where($this->foreignKey, '=', $this->parent->{$this->localKey})
                ->whereNotNull($this->foreignKey);
        }
    }

    /**
     * @return Model
     * @throws \Pecee\Pixie\Exception
     */
    public function getResults(): Model
    {
        return $this->related->first() ?: $this->getDefaultFor($this->related);
    }

}