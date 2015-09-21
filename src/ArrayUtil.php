<?php
namespace Pecee;
class ArrayUtil {
	/**
	 * Remove multiple elements from beginning of array
	 * @param array $array|null
	 * @param int $length
	 * @return array|null
	 */
	public static function shift(array &$array, $length) {
		if(count($array) > $length) {
			for($i=0;$i<$length;$i++) {
				array_shift($array);
			}
			return $array;
		}
		return null;
	}

	public static function sortByKey(array $array, $keyIndex){
		foreach($array as $k=>$v) {
			$b[$k] = strtolower($v[$keyIndex]);
		}
		arsort($b,true);
		foreach($b as $key=>$val) {
			$c[] = $array[$key];
		}
		return $c;
	}

	public static function filter(array $array, $allowEmpty=true){
		foreach($array as $key=>$arr){
			if(is_null($arr)) {
				unset($array[$key]);
			} elseif(empty($arr) && !$allowEmpty) {
				unset($array[$key]);
			}
		}
		return $array;
	}

	public static function median($arr) {
		sort($arr);
		$count = count($arr); //total numbers in array
		$middleval = floor(($count-1)/2); // find the middle value, or the lowest middle value
		$median=0;
		if($count % 2) { // odd number, middle is the median
			$median = $arr[$middleval];
		} else { // even number, calculate avg of 2 medians
			$low = $arr[$middleval];
			$high = $arr[$middleval+1];
			$median = (($low+$high)/2);
		}
		return $median;
	}

	public static function average($arr) {
		$count = count($arr); //total numbers in array
		$total=0;
		foreach ($arr as $value) {
			$total = ($total + $value); // total value of array numbers
		}
		$average = ($total/$count); // get average value
		return $average;
	}

	public static function toStdClass($array) {
		if (is_array($array)) {
			return (object) array_map(__METHOD__, $array);
		}
		return $array;
	}

	/*
	* @param $key string
	* @param $arr array
	* @return null|string|array
	*/
	public static function valueRecursive(array $arr, $key = null){
		$val = array();
		array_walk_recursive($arr, function($v, $k) use($key, &$val){
			if(is_null($key) || $k == $key) {
				array_push($val, $v);
			}
		});
		return $val;
	}

	public static function append(&$array1,$array2) {
		for($i = 0;$i < count($array2);$i++) {
			array_push($array1,$array2[$i]);
		}
		return $array1;
	}
}