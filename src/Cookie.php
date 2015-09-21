<?php
namespace Pecee;
class Cookie {

	public static function create($id, $value, $time = null, $domain = null, $secure = null) {
		$time=($time === null) ? time()+60*60*24*6004 : $time;
		if(is_null($domain)) {
			$sub = explode('.',$_SERVER['HTTP_HOST']);
			$domain = (count($sub) > 2) ? $_SERVER['HTTP_HOST'] : '.' . $_SERVER['HTTP_HOST'];
		}
		return (@setCookie($id, $value, (($time > 0) ? $time : null), '/', $domain, $secure));
	}

	public static function get($id,$defaultValue = null) {
		return isset($_COOKIE[$id]) ? $_COOKIE[$id] : $defaultValue;
	}

	public static function delete($id) {
		self::create($id, '', time()-999);
	}

	public static function exists($id) {
		return (isset($_COOKIE[$id]));
	}
}