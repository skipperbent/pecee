<?php
namespace Pecee\Model\User;
use Pecee\Date;
use Pecee\DB\DBTable;
use Pecee\Model\Model;

class UserReset extends Model {
	public function __construct($userId = null) {

        $table = new DBTable();
        $table->column('user_id')->bigint()->index();
        $table->column('key')->string(32)->index();
        $table->column('created_date')->datetime()->index();

		parent::__construct($table);

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
			self::nonQuery('UPDATE `user` SET `password` = %s WHERE `user_id` = %s LIMIT 1', md5($newPassword), $reset->user_id);
			return $reset->user_id;
		}
		return null;
	}
}