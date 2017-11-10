<?php
namespace Pecee;

use Carbon\Carbon;

class Cookie
{

    /**
     * Set cookie
     * @param string $id
     * @param string $value
     * @param int $expireTime
     * @param string|null $domain
     * @param bool|null $secure
     * @param string $path
     * @return bool
     */
    public static function create($id, $value, $expireTime = null, $domain = null, $secure = null, $path = '/')
    {
        $expireTime = ($expireTime === null) ? Carbon::now()->addYear(10)->timestamp : $expireTime;
        $expireTime = ($expireTime > 0) ? $expireTime : null;

        if ($domain === null) {
            $domain = ((substr_count(request()->getHost(), '.') + 1) > 2) ? request()->getHost() : '.' . request()->getHost();
        }

        return setcookie($id, $value, $expireTime, $path, $domain, $secure);
    }

    public static function get($id, $defaultValue = null)
    {
        return static::exists($id) ? $_COOKIE[$id] : $defaultValue;
    }

    public static function delete($id)
    {
        if (static::exists($id) === true) {
            static::create($id, null, time() - 99);
        }
    }

    public static function exists($id)
    {
        return isset($_COOKIE[$id]);
    }

}