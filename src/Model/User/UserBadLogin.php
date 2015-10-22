<?php
namespace Pecee\Model\User;
use Pecee\Date;
use Pecee\DB\DBTable;
use Pecee\Model\Model;
use Pecee\Server;

class UserBadLogin extends Model {
	public function __construct() {

        $table = new DBTable();
        $table->column('id')->bigint()->primary()->increment();
        $table->column('username')->string(300)->index();
        $table->column('created_date')->datetime()->index();
        $table->column('ip')->string(50)->index();
        $table->column('active')->bool()->nullable()->index();

		parent::__construct($table);

        $this->ip = request()->getIp();
        $this->created_date = Date::toDateTime();
	}

    public static function track($username) {
        $login = new static ();
        $login->username = $username;
        $login->save();
    }

	public static function checkBadLogin() {

        $trackQuery = self::fetchOne('SELECT `created_date`, COUNT(`ip`) AS `request_count` FROM {table} WHERE `ip` = %s AND `active` = 1 GROUP BY `ip` ORDER BY `request_count` DESC', request()->getIp());

        if($trackQuery->hasRow()) {
            $lastLoginTimeStamp = $trackQuery->created_date;
            $countRequestsFromIP = $trackQuery->request_count;
            $lastLoginMinutesAgo = round((time()-strtotime($lastLoginTimeStamp))/60);
            return ($lastLoginMinutesAgo < 30 && $countRequestsFromIP > 20);
        }
        return false;
	}

	public static function reset() {
        self::nonQuery('UPDATE {table} SET `active` = 0 WHERE `ipAddress` = %s', request()->getIp());
	}
}