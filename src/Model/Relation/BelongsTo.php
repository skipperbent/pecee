<?php

namespace Pecee\Model\Relation;

use Pecee\Model\Model;
use Pecee\Model\ModelRelation;

class BelongsTo extends ModelRelation
{

    protected $foreignKey;
    protected $ownerKey;
    protected $relation;

    protected $child;

    public function __construct(Model $related, Model $child, $foreignKey, $ownerKey, $relation)
    {
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
        $this->relation = $relation;

        // In the underlying base relationship class, this variable is referred to as
        // the "parent" since most relationships are not inversed. But, since this
        // one is we will create a "child" variable for much better readability.
        $this->child = $child;

        parent::__construct($related, $child);
    }

    public function addConstraints()
    {
        if (static::$constraints === true) {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
            $table = $this->related->getTable();

            $this->related->where($table . '.' . $this->ownerKey, '=', $this->child->{$this->foreignKey});
        }
    }

    /**
     * @return Model
     * @throws \Pecee\Pixie\Exception
     */
    public function getResults()
    {
        if ($this->child->{$this->foreignKey} !== null) {
            return $this->related->first() ?: $this->getDefaultFor($this->related);
        }

        return $this->getDefaultFor($this->related);
    }

}