<?php
namespace Pecee\Model\User;

use Pecee\Model\Model;

class UserData extends Model {

	const USER_IDENTIFIER_KEY = 'user_id';

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

    public function exists()
    {
        if($this->{$this->primary} === null) {
            return false;
        }

        return self::scalar('SELECT `key` FROM {table} WHERE `key` = %s AND `'. static::USER_IDENTIFIER_KEY .'` = %s', $this->key, $this->user_id);
    }


    public function save() {
		if($this->exists()) {
			parent::update();
		} else {
			parent::save();
		}
	}

	public static function removeAll($userId) {
		self::nonQuery('DELETE FROM {table} WHERE `'. static::USER_IDENTIFIER_KEY .'` = %s', array($userId));
	}

	public static function getByUserId($userId) {
		return self::fetchAll('SELECT * FROM {table} WHERE `'. static::USER_IDENTIFIER_KEY .'` = %s', array($userId));
	}
}