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
        static::start();

        if (static::exists($id) === true) {
            unset($_SESSION[$id]);
            return true;
        }

        return false;
    }

    public static function exists($id)
    {
        return isset($_SESSION[$id]);
    }

    /**
     * @param string $id
     * @param string $value
     * @throws \RuntimeException
     */
    public static function set($id, $value)
    {
        static::start();

        $data = [
            serialize($value),
            static::getSecret(),
        ];

        $data = Guid::encrypt(static::getSecret(), implode('|', $data));

        $_SESSION[$id] = $data;
    }

    public static function get($id, $defaultValue = null)
    {
        static::start();

        if (static::exists($id) === true) {

            $value = $_SESSION[$id];

            if (trim($value) !== '') {

                $value = Guid::decrypt(static::getSecret(), $value);
                $data = explode('|', $value);

                if (\is_array($data) && trim(end($data)) === static::getSecret()) {
                    return unserialize($data[0], ['allowed_classes' => true]);
                }

            }
        }

        return $defaultValue;
    }
}