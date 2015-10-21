<?php
namespace Pecee\Model\User;
use Pecee\DB\DBTable;
use Pecee\Model\Model;

class UserBadLogin extends Model {
	public function __construct() {

        $table = new DBTable();
        $table->column('userBadLoginId')->bigint()->primary()->increment();
        $table->column('username')->string(300)->index();
        $table->column('date')->datetime()->index();
        $table->column('ipAddress')->string(50)->index();
        $table->column('active')->bool()->nullable()->index();

		parent::__construct($table);

        $this->ipAddress = \Pecee\Server::getRemoteAddr();
        $this->date = \Pecee\Date::toDateTime();
	}

    public static function track($username) {
        $login = new static ();
        $login->username = $username;
        $login->save();
    }
	
	public static function checkBadLogin() {
        $trackQuery = self::fetchOne('SELECT `date`, COUNT(`ipAddress`) AS `requestFromIp` FROM {table} WHERE `ipAddress` = %s AND `active` = 1 GROUP BY `ipAddress` ORDER BY `requestFromIp` DESC', \Pecee\Server::GetRemoteAddr());
        if($trackQuery->hasRow()) {
            $lastLoginTimeStamp = $trackQuery->date;
            $countRequestsFromIP = $trackQuery->requestFromIp;
            $lastLoginMinutesAgo = round((time()-strtotime($lastLoginTimeStamp))/60);
            return ($lastLoginMinutesAgo < 30 && $countRequestsFromIP > 20);
        }
        return false;
	}
	
	public static function reset() {
        self::NonQuery('UPDATE {table} SET `active` = 0 WHERE `ipAddress` = %s', \Pecee\Server::getRemoteAddr());
	}
}