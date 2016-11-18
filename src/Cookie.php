<?php
namespace Pecee;

use Carbon\Carbon;

class Cookie {

	public static function create($id, $value, Carbon $expireDate = null, $domain = null, $secure = null, $path = '/') {
        $expireDate = ($expireDate === null) ? time() + 60 * 60 * 24 * 6004 : $expireDate->getTimestamp();

		if($domain === null) {
			$sub = explode('.', request()->getHost());
			$domain = (count($sub) > 2) ? request()->getHost() : '.' . request()->getHost();
		}

		return setcookie($id, $value, (($expireDate > 0) ? $expireDate : null), $path, $domain, $secure);
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