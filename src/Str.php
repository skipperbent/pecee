<?php

namespace Pecee;

class Str
{

    public static function getFirstOrDefault($value, $default = null)
    {
        return ($value !== null && trim($value) !== '') ? trim($value) : $default;
    }

    public static function encode($source, $encoding = 'UTF-8')
    {
        return mb_convert_encoding($source, 'HTML-ENTITIES', $encoding);
    }

    public static function isUtf8($str)
    {
        return ($str === mb_convert_encoding(mb_convert_encoding($str, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'));
    }

    public static function substr($text, $maxLength, $end = '...', $encoding = 'UTF-8')
    {
        if (strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength, $encoding) . $end;
        }

        return $text;
    }

    public static function wordWrap($text, $limit)
    {
        $words = explode(' ', $text);

        return join(' ', array_splice($words, 0, $limit));
    }

    public static function base64Encode($obj)
    {
        return base64_encode(serialize($obj));
    }

    public static function base64Decode($str, $defaultValue = null)
    {
        $req = base64_decode($str);
        if ($req !== false) {
            $req = unserialize($req);
            if ($req !== false) {
                return $req;
            }
        }

        return $defaultValue;
    }

    public static function deCamelize($word, $separator = '_')
    {
        return preg_replace_callback('/(^|[a-z])([A-Z])/',
            function ($matches) use ($separator) {
                return strtolower('' !== $matches[1] ? $matches[1] . $separator . $matches[2] : $matches[2]);
            },
            $word
        );
    }

    public static function camelize($word, $separator = '_')
    {
        $word = preg_replace_callback('/(^|' . $separator . ')([a-z])/', function ($matches) {
            return strtoupper($matches[2]);
        }, strtolower($word));
        $word[0] = strtolower($word[0]);

        return $word;
    }

    /**
     * Returns weather the $value is a valid email.
     * @param string $email
     * @return bool
     */
    public static function isEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

}