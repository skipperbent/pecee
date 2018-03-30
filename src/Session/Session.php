<?php

namespace Pecee\Session;

use Pecee\Guid;

class Session
{
    private static $active = false;

    public static function start()
    {
        if (static::isActive() === false) {
            session_name('pecee_session');
            session_start();
            static::$active = true;
        }
    }

    public static function getSecret()
    {
        return env('APP_SECRET', 'NoApplicationSecretDefined');
    }

    public static function isActive()
    {
        return static::$active;
    }

    public static function destroy($id)
    {
        unset($_SESSION[$id]);
    }

    public static function exists($id)
    {
        return isset($_SESSION[$id]);
    }

    public static function set($id, $value)
    {
        $data = [
            serialize($value),
            static::getSecret(),
        ];

        $data = Guid::encrypt(static::getSecret(), join('|', $data));

        $_SESSION[$id] = $data;
    }

    public static function get($id, $defaultValue = null)
    {
        if (static::exists($id) === true) {

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