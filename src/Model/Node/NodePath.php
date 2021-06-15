<?php

namespace Pecee\Model\Node;

use Pecee\Model\Model;

class NodePath extends Model
{
    protected bool $timestamps = false;

    protected array $columns = [
        'node_id',
        'parent_node_id',
        'order',
    ];

    public static function store(string $nodeId, array $path): void
    {
        static::clear($nodeId);

        $pathData = [];
        $order = 0;
        foreach ($path as $id) {
            if ($id === '0' || $id === '' || $nodeId === '') {
                continue;
            }

            $pathData[] = ['node_id' => $nodeId, 'parent_node_id' => $id, 'order' => $order];
            $order++;
        }

        if (count($pathData) > 0) {
            static::instance()->getQuery()->insert($pathData);
        }
    }

    public static function getPath(string $nodeId): array
    {
        return array_values(
            static::instance()
                ->select(['parent_node_id'])
                ->where('node_id', '=', $nodeId)
                ->all()
                ->toArray(['parent_node_id'])
            ['parent_node_id']
        );
    }

    public static function clear(string $nodeId): void
    {
        static::instance()->where('node_id', '=', $nodeId)->delete();
    }

}