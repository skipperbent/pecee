<?php
namespace Pecee\Model\User;
use Pecee\Date;
use Pecee\DB\DBTable;

class UserReset extends \Pecee\Model\Model {
	public function __construct($userId = null) {

        $table = new DBTable();
        $table->column('userId')->bigint()->index();
        $table->column('key')->string(32)->index();
        $table->column('date')->datetime()->index();

		parent::__construct($table);

        $this->userId = $userId;
        $this->key = md5(uniqid());
        $this->date = Date::ToDateTime();
	}
	
	public static function getByKey($key) {
		return self::FetchOne('SELECT * FROM {table} WHERE `key` = %s', array($key));
	}
	
	public static function confirm($Key, $Password) {
		$reset = self::FetchOne('SELECT * FROM {table} WHERE `key` = %s', $Key);
		if($reset->hasRow()) {
			$reset->delete();
			self::NonQuery('DELETE FROM {table} WHERE `key` = %s LIMIT 1', $Key);
			self::NonQuery('UPDATE `user` SET `password` = %s WHERE `userId` = %s LIMIT 1', md5($Password), $reset->getUserId());
			return $reset->getUserId();
		}
		return null;
	}
}