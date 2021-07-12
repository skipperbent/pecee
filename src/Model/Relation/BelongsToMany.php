<?php

namespace Pecee\Model\Relation;

use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\Model;
use Pecee\Model\ModelRelation;

class BelongsToMany extends ModelRelation
{

    protected string $table;
    protected string $parentKey;
    protected string $relatedKey;
    protected ?string $relationName;
    protected string $relatedPivotKey;
    protected string $foreignPivotKey;

    public function __construct(Model $related, Model $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null)
    {
        $this->table = $table;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relationName = $relationName;

        parent::__construct($related, $parent);
    }

    /**
     * @throws \Pecee\Pixie\Exception
     */
    public function addConstraints(): void
    {
        $this->performJoin();

        if ($this->constraints === true) {
            $this->addWhereConstraints();
        }
    }

    /**
     * @return ModelCollection|static[]
     * @throws \Pecee\Pixie\Exception
     */
    public function getResults(): ModelCollection
    {
        return $this->all();
    }

    /**
     * @param Model|null $model
     * @return static
     * @throws \Pecee\Pixie\Exception
     */
    protected function performJoin(Model $model = null): self
    {
        $model = $model ?: $this->related;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.

        $model->join($this->table, "{$this->related->getTable()}.{$this->relatedKey}", '=', $this->getQualifiedRelatedPivotKeyName());

        return $this;
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return static
     */
    protected function addWhereConstraints(): self
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
    public function getQualifiedForeignPivotKeyName(): string
    {
        return "{$this->table}.{$this->foreignPivotKey}";
    }

    /**
     * Get the fully qualified "related key" for the relation.
     *
     * @return string
     */
    public function getQualifiedRelatedPivotKeyName(): string
    {
        return "{$this->table}.{$this->relatedPivotKey}";
    }

}