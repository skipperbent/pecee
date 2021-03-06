<?php

namespace Pecee;

class Guid
{

    /**
     * Create new guid
     *
     * @param bool $separator
     * @return mixed|string
     * @throws \Exception
     */
    public static function create(bool $separator = false): string
    {
        if (\function_exists('com_create_guid')) {
            $guid = trim(com_create_guid(), '{}');

            return ($separator === false) ? str_replace('-', '', $guid) : $guid;
        }

        $pattern = ($separator === false) ? '%04X%04X%04X%04X%04X%04X%04X%04X' : '%04X%04X-%04X-%04X-%04X-%04X%04X%04X';

        return sprintf($pattern,
            random_int(0, 65535), random_int(0, 65535), random_int(0, 65535),
            random_int(16384, 20479), random_int(32768, 49151), random_int(0, 65535),
            random_int(0, 65535), random_int(0, 65535));
    }

    /**
     * Encrypt string
     *
     * @param string $key
     * @param string $input
     * @param string|null $method
     * @return string
     * @throws \RuntimeException
     */
    public static function encrypt($key, $input, $method = null): string
    {
        if ($method === null) {
            $method = app()->getEncryptionMethod();
        }

        $key = substr(hash('sha256', $key, true), 0, 16);

        try {
            $iv = \random_bytes(16);
        } catch (\Exception $e) {
            throw new \RuntimeException('IV generation failed ' . $e->getMessage(), $e->getCode());
        }

        $input = openssl_encrypt($input, $method, $key, 0, $iv);

        return base64_encode($input . '|' . bin2hex($iv));
    }

    /**
     * Decrypt key
     *
     * @param string $key
     * @param string $data
     * @param string|null $method
     * @return bool|string
     */
    public static function decrypt($key, $data, $method = null)
    {
        if ($method === null) {
            $method = app()->getEncryptionMethod();
        }

        $key = (string)substr(hash('sha256', $key, true), 0, 16);

        [$data, $iv] = explode('|', base64_decode($data));

        $binary = hex2bin($iv);
        if ($binary === false) {
            return false;
        }

        $data = openssl_decrypt($data, $method, $key, 0, $binary);

        return $data;
    }

    /**
     * Creates an random password, with a given length.
     *
     * @param int $length
     * @return string
     */
    public static function generateHash(int $length = 6): string
    {
        $seed = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'); // and any other characters

        $hash = '';

        foreach (array_rand($seed, $length) as $k) {
            $hash .= $seed[$k];
        }

        return $hash;
    }

    /**
     * Creates random very unique string
     *
     * @return string
     */
    public static function generateSalt(): string
    {
        return password_hash(uniqid(mt_rand(), true), PASSWORD_BCRYPT);
    }

}