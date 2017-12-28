<?php

namespace Pecee\Model\Relation;

use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\Model;
use Pecee\Model\ModelRelation;

class BelongsToMany extends ModelRelation
{

    protected $table;
    protected $parentKey;
    protected $relatedKey;
    protected $relationName;
    protected $relatedPivotKey;
    protected $foreignPivotKey;

    public function __construct(Model $related, Model $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null)
    {
        $this->table = $table;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
        $this->relationName = $relationName;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->foreignPivotKey = $foreignPivotKey;

        parent::__construct($related, $parent);
    }

    /**
     * @throws \Pecee\Pixie\Exception
     */
    public function addConstraints()
    {
        $this->performJoin();

        if (static::$constraints === true) {
            $this->addWhereConstraints();
        }
    }

    /**
     * @return ModelCollection
     * @throws \Pecee\Pixie\Exception
     */
    public function getResults()
    {
        return $this->related->all();
    }

    /**
     * @param Model|null $model
     * @return $this
     * @throws \Pecee\Pixie\Exception
     */
    protected function performJoin(Model $model = null)
    {
        $model = $model ?: $this->related;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
        $baseTable = $this->related->getTable();

        $key = $baseTable . '.' . $this->relatedKey;

        $model->join($this->table, $key, '=', $this->getQualifiedRelatedPivotKeyName());

        return $this;
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        $this->related->where(
            $this->getQualifiedForeignPivotKeyName(), '=', $this->parent->{$this->parentKey}
        );

        return $this;
    }

    /**
     * Get the fully qualified foreign key for the relation.
     *
     * @return string
     */
    public function getQualifiedForeignPivotKeyName()
    {
        return $this->table . '.' . $this->foreignPivotKey;
    }

    /**
     * Get the fully qualified "related key" for the relation.
     *
     * @return string
     */
    public function getQualifiedRelatedPivotKeyName()
    {
        return $this->table . '.' . $this->relatedPivotKey;
    }

}