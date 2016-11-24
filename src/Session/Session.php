<?php
namespace Pecee\Session;

use Pecee\Guid;

class Session
{

	public static function start()
	{
		if (!static::isActive()) {
			session_name('pecee_session');
			session_start();
		}
	}

	public static function getSecret()
	{
		return md5(env('APP_SECRET', 'NoApplicationSecretDefined'));
	}

	public static function isActive()
	{
		return isset($_SESSION);
	}

	public static function destroy($id)
	{
		if (static::exists($id)) {
			unset($_SESSION[$id]);

			return true;
		}

		return false;
	}

	public static function exists($id)
	{
		return isset($_SESSION[$id]);
	}

	public static function set($id, $value)
	{
		$data = [serialize($value), static::getSecret()];
		$data = Guid::encrypt(join('|', $data), static::getSecret());
		$_SESSION[$id] = $data;
	}

	public static function get($id, $defaultValue = null)
	{
		if (static::exists($id)) {
			$value = $_SESSION[$id];
			if (trim($value) !== '') {
				$value = Guid::decrypt($value, static::getSecret());
				$data = explode('|', $value);
				if (is_array($data) && trim(end($data)) === static::getSecret()) {
					return unserialize($data[0]);
				}
			}
		}

		return $defaultValue;
	}
}