<?php
namespace Pecee\Session;

use Pecee\Mcrypt;

class Session {

	public static function start() {
		if(!static::isActive()) {
			session_name('pecee_session');
			session_start();
		}
	}

	public static function getSecret() {
		return md5(env('APP_SECRET', 'NoApplicationSecretDefined'));
	}

 	public static function isActive() {
		return (session_id() === '');
	}

	public static function destroy($id) {
		if(static::exists($id)) {
			unset($_SESSION[$id]);
			return true;
		}
		
		return false;
	}

	public static function exists($id) {
		return isset($_SESSION[$id]);
	}

	public static function set($id, $value) {
        $data = array(serialize($value), static::getSecret());
        $data = Mcrypt::encrypt(join('|', $data), static::getSecret());
        $_SESSION[$id] = $data;
    }

	public static function get($id, $defaultValue = null) {
        if(static::exists($id)) {
            $value = $_SESSION[$id];
            if (trim($value) !== '') {
                $value = Mcrypt::decrypt($value, static::getSecret());
                $data = explode('|', $value);
                if (is_array($data) && trim(end($data)) === static::getSecret()) {
                    return unserialize($data[0]);
                }
            }
        }

        return $defaultValue;
	}
}