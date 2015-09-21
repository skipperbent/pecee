<?php
namespace Pecee;
class Server {

	public static function getRemoteAddr() {
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		if(isset($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}
		return null;
	}

	public static function getReferer() {
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	}

	public static function getUserAgent() {
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	}
}