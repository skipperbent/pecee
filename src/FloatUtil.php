<?php
namespace Pecee;

class FloatUtil
{

    public static function isFloat($val): bool
    {
        return \is_float(filter_var($val, FILTER_VALIDATE_FLOAT));
    }

    public static function parse($val): float
    {
        return static::isFloat($val) ? $val : (float)str_replace(['.', ','], ['', '.'], $val);
    }

}