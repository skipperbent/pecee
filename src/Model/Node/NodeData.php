<?php

namespace Pecee\Model\Node;

use Pecee\Model\Model;

class NodeData extends Model
{
    protected string $table = 'node_data';
    protected array $columns = [
        'id',
        'node_id',
        'key',
        'value',
    ];

    protected bool $timestamps = false;

    public function exists(): bool
    {
        if ($this->{$this->primaryKey} === null || $this->node_id === null) {
            return false;
        }

        $id = static::instance()->select([$this->primaryKey])->where('key', $this->key)->filerNodeId($this->node_id)->first();

        return ($id !== null);
    }

    public function clear($nodeId)
    {
        return $this->where('node_id', $nodeId)->delete();
    }

    public function filerNodeId($nodeId)
    {
        return $this->where('node_id', '=', $nodeId);
    }

    public function filterNodeIds(array $nodeIds)
    {
        return $this->whereIn('node_id', $nodeIds);
    }
}