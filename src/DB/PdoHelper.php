<?php
namespace Pecee\DB;

use Pecee\Integer;

class PdoHelper {

    public static function parseArgs($args, $offset) {
        if(is_array($args) && count($args) > $offset) {
            return array_slice($args, $offset);
        }
        return $args;
    }

    /**
     * Escapes query and formats it with arguments
     * @param string $query
     * @param array|null $args
     * @return string
     */
    public static function formatQuery($query, $args=null) {
        if(is_array($args) && count($args) > 0) {
            $a=array();
            foreach($args as $arg) {
                if(is_null($arg)) {
                    $a[] =  'null';
                } elseif(Integer::isInteger($arg)) {
                    $a[] =  sprintf("%s", self::escape($arg));
                } else {
                    $a[] =  sprintf("'%s'", self::escape($arg));
                }
            }
            if(count($a) > 0 && $query) {
                return vsprintf($query, $a);
            }
        }
        return $query;
    }

    public static function joinArray(array $array, $isFields=false) {
        $statement = array();
        foreach($array as $arr) {
            if($isFields) {
                $statement[] = sprintf('`%s`', $arr);
            } else {
                $statement[] = Pdo::getInstance()->getConnection()->quote($arr);
            }
        }
        return join(',', $statement);
    }

    public static function escape($value) {
        return trim(Pdo::getInstance()->getConnection()->quote($value), '\'');
    }

}