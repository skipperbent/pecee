<?php
namespace Pecee\Model\User;

use Pecee\Model\Model;

class UserBadLogin extends Model
{

    public const TIMEOUT_MINUTES = 30;
    public const MAX_REQUEST_PER_IP = 10;

    protected string $table = 'user_bad_login';

    protected array $columns = [
        'id',
        'username',
        'ip',
        'active',
    ];

    public function __construct()
    {

        parent::__construct();

        $this->ip = request()->getIp();
        $this->active = true;
    }

    public static function track($username)
    {
        static::instance()->save([
            'username' => trim($username),
        ]);
    }

    public static function checkBadLogin($username)
    {

        $track = static::instance()->where('username', '=', trim($username))
            ->where('active', '=', '1')
            ->select([self::instance()->getTable() . '.*', static::instance()->getQuery()->raw('COUNT(ip) as request_count')])
            ->groupBy(['ip', 'id'])
            ->orderBy('created_at', 'DESC')
            ->first();

        if ($track !== null) {
            $lastLoginTimeStamp = $track->created_at;
            $lastLoginMinutesAgo = round((time() - strtotime($lastLoginTimeStamp)) / 60);

            return ((static::TIMEOUT_MINUTES === null || $lastLoginMinutesAgo < static::TIMEOUT_MINUTES) &&
                (static::MAX_REQUEST_PER_IP === null || $track->request_count > static::MAX_REQUEST_PER_IP));
        }

        return false;
    }

    public static function reset($username)
    {
        static::instance()->where('username', '=', $username)->update([
            'active' => 0,
        ]);
    }
}