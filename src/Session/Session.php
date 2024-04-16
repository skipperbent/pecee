<?php

namespace Pecee\Session;

use Pecee\Guid;

class Session
{
    public static string $name = 'pecee_session';
    private static bool $active = false;
    public static array $initialData = [];

    public static function start(): void
    {
        if (static::$active === false) {
            session_name(static::$name);
            session_start();
            static::$active = true;
            static::$initialData = $_SESSION;
        }
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

        if (static::$active === false) {
            static::start();
        }

        $data = [
            serialize($value),
            app()->getSecret(),
        ];

        $data = Guid::encrypt(app()->getSecret(), implode('|', $data));

        $_SESSION[$id] = $data;
    }

    public static function get($id, $defaultValue = null)
    {
        if (static::$active === false) {
            static::start();
        }

        if (static::exists($id) === true) {

            $value = $_SESSION[$id];

            if (trim($value) !== '') {

                $value = Guid::decrypt(app()->getSecret(), $value);
                $data = explode('|', $value);

                if (is_array($data) && trim(end($data)) === app()->getSecret()) {
                    return unserialize($data[0], ['allowed_classes' => true]);
                }

            }
        }

        return $defaultValue;
    }
}