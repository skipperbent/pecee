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
     * @param bool $secure
     * @param string $path
     * @return bool
     */
    public static function create(string $id, string $value, ?Carbon $expireTime = null, ?string $domain = null, bool $secure = false, string $path = '/'): bool
    {
        $expireTime = $expireTime ?? Carbon::now()->addYears(10);
        $expireTime = ($expireTime > 0) ? $expireTime : null;

        $params = session_get_cookie_params();

        return setcookie($id, $value, $expireTime->time, $path ?? $params['path'], $domain ?? $params['domain'], $secure);
    }

    public static function get(string $id, $defaultValue = null): ?string
    {
        return static::exists($id) ? $_COOKIE[$id] : $defaultValue;
    }

    public static function delete(string $id): void
    {
        if (static::exists($id) === true) {
            static::create($id, '', time() - 99);
        }
    }

    public static function exists(string $id): bool
    {
        return isset($_COOKIE[$id]);
    }

}