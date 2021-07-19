<?php

namespace Pecee\Session;

use Pecee\Guid;

class Session
{
    public static string $sessionName = 'pecee_session';
    private static bool $active = false;

    public static function start(): void
    {
        if (static::$active === false) {
            session_name(static::$sessionName);
            session_start();
            static::$active = true;
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
        $_SESSION[$id] = $value;
    }

    /**
     * Encrypt and store session
     *
     * @param string $id
     * @param mixed $value
     */
    public function setEncrypted(string $id, $value): void
    {
        $data = [
            serialize($value),
            app()->getSecret(),
        ];

        $data = Guid::encrypt(app()->getSecret(), implode('|', $data));

        static::set($id, $data);
    }

    public static function get(string $id, $defaultValue = null)
    {
        return $_SESSION[$id] ?? $defaultValue;
    }

    /**
     * Get decrypted session data
     *
     * @param string $id
     * @param null $defaultValue
     * @return mixed|null
     */
    public static function getDecrypted(string $id, $defaultValue = null)
    {
        $value = static::get($id);

        if ($value !== null && trim($value) !== '') {
            $value = Guid::decrypt(app()->getSecret(), $value);
            $data = explode('|', $value);

            if (\is_array($data) && trim(end($data)) === app()->getSecret()) {
                return unserialize($data[0], ['allowed_classes' => true]);
            }
        }

        return $defaultValue;
    }

}