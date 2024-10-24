<?php

namespace Pecee;

use Laravel\SerializableClosure\SerializableClosure;

class ArrayUtil
{

    /**
     * Behaves like array_util but works on multidimensional arrays.
     *
     * @param array $array
     * @param callable|null $callback
     * @return array
     */
    public static function filter(array $array, ?callable $callback = null): array
    {
        $array = array_map(static function ($item) {
            return is_array($item) ? static::filter($item) : $item;
        }, $array);

        if ($callback === null) {
            $callback = static function ($value) {
                return !empty($value);
            };
        }

        return array_filter($array, $callback);
    }

    /**
     * Remove empty and null values from array but allows false and 0.
     *
     * @param array $array
     * @return array
     */
    public static function clean(array $array): array
    {
        return array_filter($array, static function ($value) {
            return ($value !== null && $value !== '');
        });
    }

    public static function serialize(array $input): array
    {
        $output = [];
        foreach ($input as $key => $value) {
            $output[$key] = ($value instanceof \Closure) ? new SerializableClosure($value) : $value;
        }
        return $output;
    }

    public static function unserialize(array $input): array
    {
        $output = [];
        foreach ($input as $key => $value) {
            $output[$key] = ($value instanceof SerializableClosure) ? $value->getClosure() : $value;
        }

        return $output;
    }

    public static function average(array $arr)
    {
        $count = count($arr); //total numbers in array
        $total = 0;
        foreach ($arr as $value) {
            $total += $value; // total value of array numbers
        }

        return ($total / $count); // get average value
    }

    public static function toStdClass($array): \stdClass
    {
        if (is_array($array)) {
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