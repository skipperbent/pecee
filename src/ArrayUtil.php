<?php

namespace Pecee;

class ArrayUtil
{

    public static function filter(array $array, $allowEmpty = true): array
    {
        foreach ($array as $key => $value) {
            if ($value === null || ($allowEmpty === false && trim($value) !== '')) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public static function average(array $arr)
    {
        $count = \count($arr); //total numbers in array
        $total = 0;
        foreach ($arr as $value) {
            $total += $value; // total value of array numbers
        }

        return ($total / $count); // get average value
    }

    public static function toStdClass($array): \stdClass
    {
        if (\is_array($array)) {
            return (object)array_map(__METHOD__, $array);
        }

        return $array;
    }

    /*
    * @param $key string
    * @param $arr array
    * @return null|string|array
    */
    public static function valueRecursive(array $arr, $key = null): array
    {
        $val = [];
        array_walk_recursive($arr, function ($v, $k) use ($key, &$val) {
            if ($k === $key || $key === null) {
                $val[] = $v;
            }
        });

        return $val;
    }

    public static function append(array &$array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            $array1[] = $value;
        }

        return $array1;
    }

}