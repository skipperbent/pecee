<?php
namespace Pecee;

class Str {

    public static function getFirstOrDefault($value, $default = null){
        return ($value !== null && trim($value) !== '') ? trim($value) : $default;
    }

    public static function isUtf8($str) {
        return ($str === mb_convert_encoding(mb_convert_encoding($str, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'));
    }

    public static function substr($text, $maxLength, $end = '...', $encoding = 'UTF-8') {
        if(strlen($text) > $maxLength) {
            $output = mb_substr($text, 0, $maxLength, $encoding);
            if(strlen($text) > $maxLength)
                $output .= $end;
            return $output;
        }
        return $text;
    }

    public static function wordWrap($text, $limit) {
        $words = explode(' ', $text);
        return join(' ', array_splice($words, 0, $limit));
    }

    public static function htmlEntities($value) {
        if(is_array($value)) {
            $newArr = array();
            foreach($value as $key => $v) {
                $newArr[$key] = htmlentities($v, null, 'UTF-8');
            }
            return $newArr;
        }
        return htmlentities($value, ENT_QUOTES, 'UTF-8');
    }

    public static function htmlEntitiesDecode($value) {
        if(is_array($value)) {
            $newArr = array();
            foreach($value as $key => $v) {
                $newArr[$key] = html_entity_decode($v, null, 'UTF-8');
            }
            return $newArr;
        }
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }

    public static function base64Encode($obj) {
        return base64_encode(serialize($obj));
    }

    public static function base64Decode($str, $defaultValue = null) {
        $req = base64_decode($str);
        if($req !== false) {
            $req = unserialize($req);
            if($req) {
                return $req;
            }
        }
        return $defaultValue;
    }

    public static function decamelize($word) {
        return preg_replace_callback('/(^|[a-z])([A-Z])/',
            function($matches) {
                return strtolower(strlen($matches[1]) ? $matches[1] . '_' . $matches[2] : $matches[2]);
            },
            $word
        );
    }

    public static function camelize($word) {
        $word = preg_replace_callback('/(^|_)([a-z])/', function($matches) {
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
    public static function isEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

}