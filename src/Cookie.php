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
    public static function create($id, $value, $expireTime = null, $domain = null, bool $secure = false, $path = '/'): bool
    {
        $expireTime = $expireTime ?? Carbon::now()->addYear(10)->timestamp;
        $expireTime = ($expireTime > 0) ? $expireTime : null;

        if ($domain === null) {
            $domain = ((substr_count(request()->getHost(), '.') + 1) > 2) ? request()->getHost() : '.' . request()->getHost();
        }

        return setcookie($id, $value, $expireTime, $path, $domain, $secure);
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