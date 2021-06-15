<?php

namespace Pecee\Model\Relation;

use Pecee\Model\Model;
use Pecee\Model\ModelRelation;

class BelongsTo extends ModelRelation
{

    protected string $foreignKey;
    protected string $ownerKey;
    protected string $relation;

    protected Model $child;

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

    public function addConstraints(): Model
    {
        if ($this->constraints === true) {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
            $table = $this->related->getTable();

            $this->related->where($table . '.' . $this->ownerKey, '=', $this->child->{$this->foreignKey});
        }

        return $this->related;
    }

    /**
     * @return Model|null
     * @throws \Pecee\Pixie\Exception
     */
    public function getResults(): ?Model
    {
        return $this->addConstraints()->first() ?: $this->getDefaultFor($this->related);
    }

}