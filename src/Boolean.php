<?php
namespace Pecee;

class Boolean {

    /**
     * Parse boolean
     *
     * @param string $str
     * @param bool|object $default
     * @return bool|object
     */
	public static function parse($str, $default = false) {
		$bool=filter_var($str, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		return is_null($bool) ? $default : $bool;
	}

}