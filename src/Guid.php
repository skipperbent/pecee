<?php
namespace Pecee;

class Guid
{

    public static function create($separator = false)
    {
        if (function_exists('com_create_guid')) {
            $guid = trim(com_create_guid(), '{}');

            return (!$separator) ? str_replace('-', '', $guid) : $guid;
        }
        $pattern = (!$separator) ? '%04X%04X%04X%04X%04X%04X%04X%04X' : '%04X%04X-%04X-%04X-%04X-%04X%04X%04X';

        return sprintf($pattern,
            mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535),
            mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535),
            mt_rand(0, 65535), mt_rand(0, 65535));
    }

    public static function encrypt($key, $data, $method = null)
    {
        if ($method === null) {
            $method = app()->getEncryptionMethod();
        }

        $key = substr(hash('sha256', $key, true), 0, 16);

        $isSourceStrong = false;

        $iv = openssl_random_pseudo_bytes(16, $isSourceStrong);
        if ($isSourceStrong === false || $iv === false) {
            throw new \RuntimeException('IV generation failed');
        }

        $data = openssl_encrypt($data, $method, $key, 0, $iv);

        return base64_encode($data . '|' . bin2hex($iv));
    }

    public static function decrypt($key, $data, $method = null)
    {
        if ($method === null) {
            $method = app()->getEncryptionMethod();
        }

        $key = substr(hash('sha256', $key, true), 0, 16);

        list($data, $iv) = explode('|', base64_decode($data));

        $binary = hex2bin($iv);
        if ($binary === false) {
            return false;
        }

        $data = openssl_decrypt($data, $method, $key, 0, $binary);

        return $data;
    }

    /**
     * Creates an random password, with a given length.
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public static function createRandomPassword($length)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVXYXW023456789';
        mt_srand((double)microtime() * 1000000);
        $i = 0;
        $pass = '';
        while ($i <= $length) {
            $num = mt_rand() % 33;
            $tmp = substr($chars, $num, 1);
            $pass .= $tmp;
            $i++;
        }

        return $pass;
    }

    /**
     * Creates random very unique string
     * @return string
     */
    public static function generateSalt()
    {
        return password_hash(uniqid(mt_rand(), true), PASSWORD_BCRYPT);
    }

}