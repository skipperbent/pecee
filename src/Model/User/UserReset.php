<?php
namespace Pecee\Model\User;

use Pecee\Date;
use Pecee\Model\Model;

class UserReset extends Model {

    protected $columns = [
        'id',
        'user_id',
        'key',
        'created_date'
    ];

    public function __construct($userId = null) {

        parent::__construct();

        $this->user_id = $userId;
        $this->key = md5(uniqid());
        $this->created_date = Date::toDateTime();
    }

    public function clean() {
        self::nonQuery('DELETE FROM {table} WHERE `user_id` = %s', $this->user_id);
    }

    public function save() {
        $this->clean();
        parent::save();
    }

    public static function getByKey($key) {
        return self::fetchOne('SELECT * FROM {table} WHERE `key` = %s', $key);
    }

    public static function confirm($key) {
        $reset = self::fetchOne('SELECT * FROM {table} WHERE `key` = %s', $key);
        if($reset->hasRow()) {
            $reset->delete();
            self::nonQuery('DELETE FROM {table} WHERE `user_id` = %s', $reset->user_id);
            return $reset->user_id;
        }
        return false;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getKey() {
        return $this->key;
    }
}