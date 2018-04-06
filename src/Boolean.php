<?php
namespace Pecee;

class Boolean
{

    /**
     * Parse boolean
     *
     * @param string $str
     * @param mixed $defaultValue
     * @return bool
     */
    public static function parse($str, $defaultValue = false): bool
    {
        $bool = filter_var($str, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $bool ?? $defaultValue;
    }

}