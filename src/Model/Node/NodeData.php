<?php
namespace Pecee\Model\Node;

use Pecee\DB\DBTable;
use Pecee\DB\PdoHelper;
use Pecee\Model\Model;

class NodeData extends Model {
    public function __construct() {

        $table = new DBTable('node_data');
        $table->column('id')->bigint()->primary()->increment();
        $table->column('node_id')->bigint()->index();
        $table->column('key')->string(255)->index();
        $table->column('value')->longtext();

        parent::__construct($table);
    }

    /*public function save() {
        if(self::scalar('SELECT `key` FROM {table} WHERE `key` = %s AND `node_id` = %s', $this->node_id, $this->key)) {
            parent::update();
        } else {
            parent::save();
        }
    }*/

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