<?php
namespace Pecee\Model\Node;

use Pecee\DB\PdoHelper;
use Pecee\Model\LegacyModel;

class NodeData extends LegacyModel {

    protected $table = 'node_data';
    protected $columns = [
        'id',
        'node_id',
        'key',
        'value'
    ];

    public function save() {
        if(self::scalar('SELECT `key` FROM {table} WHERE `key` = %s AND `node_id` = %s', $this->node_id, $this->key)) {
            parent::update();
        } else {
            parent::save();
        }
    }

    public static function clear($nodeId) {
        self::nonQuery('DELETE FROM {table} WHERE `node_id` = %s', array($nodeId));
    }

    public static function getByNodeIds(array $nodeIds) {
        return self::fetchAll('SELECT * FROM {table} WHERE `node_id` IN(' . PdoHelper::joinArray($nodeIds).')');
    }

    public static function getByNodeId($nodeId) {
        return self::fetchAll('SELECT * FROM {table} WHERE `node_id` = %s', array($nodeId));
    }
}