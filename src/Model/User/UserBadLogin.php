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
        $trackQuery = self::fetchOne('SELECT `date`, COUNT(`ip`) AS `requestFromIp` FROM {table} WHERE `ip` = %s AND `active` = 1 GROUP BY `ip` ORDER BY `created_date` DESC', \Pecee\Server::GetRemoteAddr());
        if($trackQuery->hasRow()) {
            $lastLoginTimeStamp = $trackQuery->date;
            $countRequestsFromIP = $trackQuery->requestFromIp;
            $lastLoginMinutesAgo = round((time()-strtotime($lastLoginTimeStamp))/60);
            return ($lastLoginMinutesAgo < 30 && $countRequestsFromIP > 20);
        }
        return false;
	}

	public static function reset() {
        self::NonQuery('UPDATE {table} SET `active` = 0 WHERE `ip` = %s', request()->getIp());
	}
}