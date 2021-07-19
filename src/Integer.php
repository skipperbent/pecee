<?php
namespace Pecee;

class Integer
{

    /**
     * Check if a given value is a counting type or if the value of the string has numbers in it.
     * @param string $str
     * @return bool
     */
    public static function isInteger(string $str): bool
    {
        return (filter_var($str, FILTER_VALIDATE_INT) !== false);
    }

    public static function isNummeric(string $val): bool
    {
        return self::isInteger($val) || is_numeric($val);
    }

}