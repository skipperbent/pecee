<?php

namespace Pecee;

class ArrayUtil
{

    /**
     * Array diff for multidimensional arrays.
     *
     * @param ...$arrays
     * @return array
     * @throws \JsonException
     */
    public static function diff(...$arrays): array
    {
        $args = [];

        // Compare all values by a json_encode
        foreach ($arrays as $key => $value) {
            $args[$key] = array_map('json_encode', $value);
        }

        $diff = array_diff(...$args);

        // Json decode the result
        return array_map(static function ($value) {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }, $diff);
    }

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

    public static function average(array $arr): int
    {
        $count = count($arr); //total numbers in array
        $total = 0;
        foreach ($arr as $value) {
            $total += $value; // total value of array numbers
        }

        return ($total / $count); // get average value
    }

    public static function toStdClass(array $array): \stdClass
    {
        if (\is_array($array)) {
            return (object)array_map(__METHOD__, $array);
        }

        return $array;
    }

    /**
     * Get recursive value from array
     *
     * @param array $arr
     * @param string|null $key
     * @return array
     */
    public static function valueRecursive(array $arr, ?string $key = null): array
    {
        $val = [];
        array_walk_recursive($arr, static function ($v, $k) use ($key, &$val) {
            if ($key === null || $k === $key) {
                $val[] = $v;
            }
        });

        return $val;
    }

    /**
     * Merge array recursively while preserving keys as array_merge_recursive doesn't preserve integer keys.
     * @param ...$arrays
     * @return array
     */
    public static function mergeRecursive(...$arrays): array
    {
        $base = array_shift($arrays);

        foreach ($arrays as $array) {
            reset($base);

            if(is_array($array) === false) {
                continue;
            }

            foreach ($array as $key => $value) {
                if (is_array($value) && @is_array($base[$key])) {
                    $base[$key] = static::mergeRecursive($base[$key], $value);
                    continue;
                }

                $base[$key] = $value;
            }
        }

        return $base;
    }

    public static function append(array &$array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            $array1[] = $value;
        }

        return $array1;
    }

}