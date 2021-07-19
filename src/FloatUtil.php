<?php
namespace Pecee;

class FloatUtil
{

    public static function isFloat(string $val): bool
    {
        return is_float(filter_var($val, FILTER_VALIDATE_FLOAT));
    }

    public static function parse(string $val): float
    {
        return static::isFloat($val) ? $val : (float)str_replace(['.', ','], ['', '.'], $val);
    }

}