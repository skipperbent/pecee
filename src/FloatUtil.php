<?php
namespace Pecee;

class FloatUtil {

	public static function isFloat($val) {
		return is_float(filter_var($val, FILTER_VALIDATE_FLOAT));
	}

	public static function parse($val) {
		return static::isFloat($val) ? $val : floatval(str_replace(['.', ','], ['', '.'], $val));
	}

}