<?php
namespace Pecee\Model\User;

use Pecee\Date;
use Pecee\Model\Model;

class UserBadLogin extends Model {

    protected $table = 'user_bad_login';

    protected $columns = [
        'id',
        'username',
        'created_date',
        'ip',
        'active'
    ];

    const TIMEOUT_MINUTES = 30;
    const MAX_REQUEST_PER_IP = 20;

	public function __construct() {

		parent::__construct();

        $this->ip = request()->getIp();
        $this->created_date = Date::toDateTime();
	}

    public static function track($username) {
        $login = new static ();
        $login->username = trim($username);
        $login->save();
    }

	public static function checkBadLogin($username) {

        $trackQuery = static::fetchOne('SELECT `created_date`, COUNT(`ip`) AS `request_count` FROM {table} WHERE `username` = %s && `active` = 1 GROUP BY `ip` ORDER BY `request_count` DESC', trim($username));

        if($trackQuery->hasRow()) {
            $lastLoginTimeStamp = $trackQuery->created_date;
            $countRequestsFromIP = $trackQuery->request_count;
            $lastLoginMinutesAgo = round((time()-strtotime($lastLoginTimeStamp))/60);
            return ((self::TIMEOUT_MINUTES === null || $lastLoginMinutesAgo < self::TIMEOUT_MINUTES) &&
                    (self::MAX_REQUEST_PER_IP === null || $countRequestsFromIP > self::MAX_REQUEST_PER_IP));
        }
        return false;
	}

	public static function reset($username) {
        static::nonQuery('UPDATE {table} SET `active` = 0 WHERE `username` = %s', trim($username));
	}
}