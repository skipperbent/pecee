<?php
namespace Pecee\Model\User;

use Pecee\Model\Model;

class UserData extends Model {

	protected $columns = [
		'id',
		'user_id',
		'key',
		'value'
	];

	public function __construct($userId = null, $key = null, $value = null) {

		parent::__construct();

		$this->user_id = $userId;
		$this->key = $key;
		$this->value = $value;
	}

	public function save() {
		if(self::scalar('SELECT `key` FROM {table} WHERE `key` = %s AND `user_id` = %s', $this->key, $this->userId)) {
			parent::update();
		} else {
			parent::save();
		}
	}

	public static function removeAll($userId) {
		self::nonQuery('DELETE FROM {table} WHERE `user_id` = %s', array($userId));
	}

	public static function getByUserId($userId) {
		return self::fetchAll('SELECT * FROM {table} WHERE `user_id` = %s', array($userId));
	}
}