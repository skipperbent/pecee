<?php

namespace Pecee\Session;

use Pecee\Guid;

class Session
{
    private static $active = false;

    public static function start(): void
    {
        if (static::$active === false) {
            session_name('pecee_session');
            session_start();
            static::$active = true;
        }
    }

    public static function getSecret(): string
    {
        return env('APP_SECRET', 'NoApplicationSecretDefined');
    }

    public static function isActive(): bool
    {
        return static::$active;
    }

    public static function destroy(string $id): void
    {
        unset($_SESSION[$id]);
    }

    public static function exists(string $id): bool
    {
        return isset($_SESSION[$id]);
    }

    /**
     * @param string $id
     * @param mixed $value
     */
    public static function set(string $id, $value): void
    {
        $data = [
            serialize($value),
            static::getSecret(),
        ];

        $data = Guid::encrypt(static::getSecret(), implode('|', $data));

        $_SESSION[$id] = $data;
    }

    public static function get($id, $defaultValue = null)
    {
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