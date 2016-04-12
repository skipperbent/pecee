<?php
namespace Pecee\Model\User;

use Carbon\Carbon;
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
    const MAX_REQUEST_PER_IP = 10;

	public function __construct() {

		parent::__construct();

        $this->ip = request()->getIp();
        $this->created_date = Carbon::now()->toDateTimeString();
        $this->active = true;
	}

    public static function track($username) {
        $login = new static();
        $login->username = trim($username);
        $login->save();
    }

	public static function checkBadLogin($username) {

        $track = static::where('username', '=', trim($username))
            ->where('active', '=', '1')
            ->select(['*', static::getQuery()->raw('COUNT(ip) as request_count')])
            ->groupBy('ip')
            ->orderBy('created_date', 'DESC')
            ->first();

        if($track !== null) {
            $lastLoginTimeStamp = $track->created_date;
            $countRequestsFromIP = $track->request_count;
            $lastLoginMinutesAgo = round((time()-strtotime($lastLoginTimeStamp))/60);

            return ((self::TIMEOUT_MINUTES === null || $lastLoginMinutesAgo < self::TIMEOUT_MINUTES) &&
                    (self::MAX_REQUEST_PER_IP === null || $countRequestsFromIP > self::MAX_REQUEST_PER_IP));
        }
        return false;
	}

	public static function reset($username) {
        static::where('username', '=', $username)->update([
            'active' => 0
        ]);
	}
}