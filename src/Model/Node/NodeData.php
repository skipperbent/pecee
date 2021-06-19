<?php

namespace Pecee\Model\Node;

use PDOStatement;
use Pecee\Guid;
use Pecee\Model\ModelMeta\ModelMetaField;

/**
 * Class NodeData
 * @package Pecee\Model\Node
 * @property int $id
 * @property string $node_id
 * @property string $key
 * @property mixed $value
 */
class NodeData extends ModelMetaField
{
    protected bool $fixedIdentifier = true;
    protected string $table = 'node_data';
    protected array $columns = [
        'id',
        'node_id',
        'key',
        'value',
    ];

    protected bool $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->id = Guid::create();
    }

    public function getDataKeyName(): string
    {
        return 'key';
    }

    public function getDataValueName(): string
    {
        return 'value';
    }

    public function exists(): bool
    {
        if ($this->{$this->primaryKey} === null || $this->node_id === null) {
            return false;
        }

        return (static::instance()->select([$this->primaryKey])->where('key', $this->key)->filterNodeId($this->node_id)->first() !== null);
    }

    public function clear(string $nodeId): PDOStatement
    {
        return $this->where('node_id', $nodeId)->delete();
    }

    public function filterNodeId(string $nodeId): self
    {
        return $this->where('node_id', '=', $nodeId);
    }

    public function filterNodeIds(array $nodeIds): self
    {
        return $this->whereIn('node_id', $nodeIds);
    }

}