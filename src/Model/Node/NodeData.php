<?php
namespace Pecee\Model\Node;
use Pecee\DB\DB;
use Pecee\DB\DBTable;
use Pecee\DB\PdoHelper;

class NodeData extends \Pecee\Model\Model {
	public function __construct() {

        $table = new DBTable('node_data');
        $table->column('nodeId')->bigint()->index();
        $table->column('key')->string(255)->index();
        $table->column('value')->longtext();

		parent::__construct($table);
	}
	public function save() {
		if(self::Scalar('SELECT `Key` FROM {table} WHERE `key` = %s AND `nodeId` = %s', $this->nodeId, $this->key)) {
			parent::update();
		} else {
			parent::save();
		}
	}

	public static function clear($nodeId) {
		self::nonQuery('DELETE FROM {table} WHERE `nodeId` = %s', array($nodeId));
	}

	public static function getByNodeIds(array $nodeIds) {
		return self::fetchAll('SELECT * FROM {table} WHERE `nodeId` IN(' . PdoHelper::joinArray($nodeIds).')');
	}

	public static function getByNodeId($nodeId) {
		return self::fetchAll('SELECT * FROM {table} WHERE `nodeId` = %s', array($nodeId));
	}
}