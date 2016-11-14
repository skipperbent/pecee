<?php
namespace Pecee\Model\User;

use Carbon\Carbon;
use Pecee\Model\Model;

class UserBadLogin extends Model {

    protected $timestamps = false;
    protected $table = 'user_bad_login';

    protected $columns = [
        'id',
        'username',
        'ip',
        'active',
        'created_at',
    ];

    const TIMEOUT_MINUTES = 30;
    const MAX_REQUEST_PER_IP = 10;

	public function __construct() {

		parent::__construct();

        $this->ip = request()->getIp();
        $this->active = true;
        $this->created_at = Carbon::now()->toDateTimeString();
	}

    public static function track($username) {
        static::instance()->save([
            'username' => trim($username),
        ]);
    }

	public static function checkBadLogin($username) {

        $track = static::instance()->where('username', '=', trim($username))
            ->where('active', '=', '1')
            ->select([self::instance()->getTable() . '.*', static::getQuery()->raw('COUNT(ip) as request_count')])
            ->groupBy('ip')
            ->orderBy('created_at', 'DESC')
            ->first();

        if($track !== null) {
            $lastLoginTimeStamp = $track->created_at;
            $lastLoginMinutesAgo = round((time()-strtotime($lastLoginTimeStamp))/60);

            return ((static::TIMEOUT_MINUTES === null || $lastLoginMinutesAgo < static::TIMEOUT_MINUTES) &&
                    (static::MAX_REQUEST_PER_IP === null || $track->request_count > static::MAX_REQUEST_PER_IP));
        }
        return false;
	}

	public static function reset($username) {
        static::instance()->where('username', '=', $username)->update([
            'active' => 0
        ]);
	}
}