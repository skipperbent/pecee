<?php
namespace Pecee;

class Mcrypt {

    public static function encrypt($key, $data, $method = 'AES-256-CBC')
    {
        $key = substr(hash('sha256', $key, true), 0, 16);

        $isSourceStrong = false;

        $iv = openssl_random_pseudo_bytes(16, $isSourceStrong);
        if ($isSourceStrong === false || $iv === false) {
            throw new \RuntimeException('IV generation failed');
        }

        $data = openssl_encrypt($data, $method, $key, 0, $iv);

        return base64_encode($data . '|' . bin2hex($iv));
    }

    public static function decrypt($key, $data, $method = 'AES-256-CBC')
    {
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

}