<?php 
namespace Pecee\Model\User;
use Pecee\DB\DBTable;

class UserData extends \Pecee\Model\Model {
	public function __construct($userId = null, $key = null, $value = null) {

        $table = new DBTable();
        $table->column('userId')->bigint()->index();
        $table->column('key')->string(255)->index();
        $table->column('value')->longtext()->nullable();

		parent::__construct($table);

        $this->userId = $userId;
        $this->key = $key;
        $this->value = $value;
	}
	public function save() {
		if(self::Scalar('SELECT `key` FROM {table} WHERE `key` = %s AND `userId` = %s', $this->key, $this->userId)) {
			parent::update();
		} else {
			parent::save();
		}
	}

	public static function RemoveAll($userId) {
		self::NonQuery('DELETE FROM {table} WHERE `userId` = %s', array($userId));
	}
	public static function GetByUserID($userId) {
		return self::FetchAll('SELECT * FROM {table} WHERE `userId` = %s', array($userId));
	}
}