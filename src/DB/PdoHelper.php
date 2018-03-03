<?php

namespace Pecee\DB;

use Pecee\Integer;

class PdoHelper
{

    public static function parseArgs($args, $offset)
    {
        if (\is_array($args) === true && \count($args) > $offset) {
            return \array_slice($args, $offset);
        }

        return $args;
    }

    /**
     * Escapes query and formats it with arguments
     * @param string $query
     * @param array|null $args
     * @return string
     */
    public static function formatQuery($query, $args = null)
    {
        if (\is_array($args) === true && \count($args) > 0) {
            $a = [];
            foreach ($args as $arg) {
                if ($arg === null) {
                    $a[] = 'null';
                } elseif (Integer::isInteger($arg)) {
                    $a[] = sprintf('%s', self::escape($arg));
                } else {
                    $a[] = sprintf('\'%s\'', self::escape($arg));
                }
            }
            if ($query !== null && \count($a) > 0) {
                return vsprintf($query, $a);
            }
        }

        return $query;
    }

    public static function joinArray(array $array, $isFields = false)
    {
        $statement = [];
        foreach ($array as $arr) {
            if ($isFields) {
                $statement[] = sprintf('`%s`', $arr);
            } else {
                if ($arr === null) {
                    $statement[] = 'NULL';
                } else {
                    $statement[] = Pdo::getInstance()->getConnection()->quote($arr);
                }
            }
        }

        return implode(',', $statement);
    }

    public static function escape($value)
    {
        return trim(Pdo::getInstance()->getConnection()->quote($value), '\'');
    }

}