<?php
namespace Pecee;

class Cookie {

    public static function create($id, $value, $expireTime = null, $domain = null, $secure = null, $path = '/') {
        $expireTime = ($expireTime === null) ? time() + 60 * 60 * 24 * 6004 : $expireTime;

        if($domain === null) {
            $sub = explode('.', request()->getHost());
            $domain = (count($sub) > 2) ? request()->getHost() : '.' . request()->getHost();
        }

        return setcookie($id, $value, (($expireTime > 0) ? $expireTime : null), $path, $domain, $secure);
    }

    public static function get($id,$defaultValue = null) {
        return static::exists($id) ? $_COOKIE[$id] : $defaultValue;
    }

    public static function delete($id) {
        static::create($id, '', time() - 999);
    }

    public static function exists($id) {
        return isset($_COOKIE[$id]);
    }

}