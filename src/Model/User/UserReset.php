<?php
namespace Pecee\Model\User;

use Pecee\Date;
use Pecee\Model\Model;

class UserReset extends Model {

    const USER_IDENTIFIER_KEY = 'user_id';

    protected $table = 'user_reset';

    protected $columns = [
        'id',
        'key',
        'created_date'
    ];

    public function __construct($userId = null) {

        parent::__construct();

        $this->columns = array_merge($this->columns, [ static::USER_IDENTIFIER_KEY ]);

        $this->{static::USER_IDENTIFIER_KEY} = $userId;
        $this->key = md5(uniqid());
        $this->created_date = Date::toDateTime();
    }

    public function clean() {
        static::nonQuery('DELETE FROM {table} WHERE `'. static::USER_IDENTIFIER_KEY .'` = %s', $this->{static::USER_IDENTIFIER_KEY});
    }

    public function save() {
        $this->clean();
        parent::save();
    }

    public static function getByKey($key) {
        return static::fetchOne('SELECT * FROM {table} WHERE `key` = %s', $key);
    }

    public static function confirm($key) {
        $reset = static::fetchOne('SELECT * FROM {table} WHERE `key` = %s', $key);
        if($reset->hasRow()) {
            $reset->delete();
            self::nonQuery('DELETE FROM {table} WHERE `'. static::USER_IDENTIFIER_KEY . '` = %s', $reset->{static::USER_IDENTIFIER_KEY});
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