<?php
namespace Pecee\Model\User;

use Pecee\Date;
use Pecee\Model\Model;

class UserReset extends Model {

	protected $columns = [
		'user_id',
		'key',
		'created_date'
	];

	public function __construct($userId = null) {

		parent::__construct();

        $this->user_id = $userId;
        $this->key = md5(uniqid());
        $this->created_date = Date::ToDateTime();
	}

	public static function getByKey($key) {
		return self::fetchOne('SELECT * FROM {table} WHERE `key` = %s', array($key));
	}

	public static function confirm($key, $newPassword) {
		$reset = self::fetchOne('SELECT * FROM {table} WHERE `key` = %s', $key);
		if($reset->hasRow()) {
			$reset->delete();
			self::nonQuery('DELETE FROM {table} WHERE `user_id` = %s', $reset->user_id);
			self::nonQuery('UPDATE `user` SET `password` = %s WHERE `id` = %s LIMIT 1', md5($newPassword), $reset->user_id);
			return $reset->user_id;
		}
		return null;
	}
}