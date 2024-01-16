<?php

namespace Pecee\Model\Relation;

use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\Model;
use Pecee\Model\ModelRelation;

class HasMany extends ModelRelation
{

    protected $localKey;
    protected $foreignKey;

    public function __construct(Model $related, Model $parent, $foreignKey, $localKey)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;

        parent::__construct($related, $parent);
    }

    public function addConstraints()
    {
        if (static::$constraints === true) {
            $this->related
                ->where($this->foreignKey, '=', $this->parent->{$this->localKey})
                ->whereNotNull($this->foreignKey);
        }
    }

    /**
     * @return Model|ModelCollection
     * @throws \Pecee\Pixie\Exception
     */
    public function getResults()
    {
        return $this->related->all() ?: $this->getDefaultFor($this->related);
    }

}