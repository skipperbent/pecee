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

        return openssl_encrypt($data, $method, $key, 0, $iv);
    }

    public static function decrypt($key, $data, $method = 'AES-256-CBC')
    {
        $key = substr(hash('sha256', $key, true), 0, 16);
        return openssl_decrypt($data, $method, $key, 0);
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