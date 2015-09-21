<?php
namespace Pecee;
class Float {
	public static function isFloat($val) {
		return is_float(filter_var($val, FILTER_VALIDATE_FLOAT));
	}
	
	public static function parseFloat($val) {
		return (self::isFloat($val)) ? $val : floatval(str_replace(array('.', ','), array('', '.'), $val));
	}
}