<?php
namespace Pecee\Session;

use Pecee\Guid;

class Session
{

    protected static function start()
    {
        if (static::isActive() === false) {
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
            static::start();
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
        static::start();
        $data = [serialize($value), static::getSecret()];
        $data = Guid::encrypt(static::getSecret(), join('|', $data));
        $_SESSION[$id] = $data;
    }

    public static function get($id, $defaultValue = null)
    {
        if (static::exists($id)) {
            $value = $_SESSION[$id];
            if (trim($value) !== '') {
                $value = Guid::decrypt(static::getSecret(), $value);
                $data = explode('|', $value);
                if (is_array($data) && trim(end($data)) === static::getSecret()) {
                    return unserialize($data[0]);
                }
            }
        }

        return $defaultValue;
    }
}