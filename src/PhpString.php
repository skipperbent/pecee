<?php
namespace Pecee;
class PhpString {

	public static function getFirstOrDefault($value, $default = null){
		return ($value !== null && trim($value) !== '') ? trim($value) : $default;
	}

	public static function isUtf8($str) {
        return ($str === mb_convert_encoding(mb_convert_encoding($str, "UTF-32", "UTF-8"), "UTF-8", "UTF-32")) ? true : false;
    }
	
	public static function substr($text, $maxLength, $end='...', $encoding = 'UTF-8') {
		if(strlen($text) > $maxLength) {
			$output = mb_substr($text, 0, $maxLength, $encoding);
			if(strlen($text) > $maxLength)
				$output .= $end;
			return $output;
		}
		return $text;
	}
	
	public static function wrap($text, $maxLength = 25) {
		return preg_replace('/([^\s]{'.$maxLength.'})(?=[^\s])/', '$1', $text);
	}
	
	public static function htmlEntities($value) {
		if( is_array($value) ) {
			$newArr = array();
			foreach($value as $key => $v) {
				$newArr[$key] = htmlentities($v, null, 'UTF-8');
			}
			return $newArr;
		}
		return htmlentities($value, ENT_QUOTES, 'UTF-8');
	}
	
	public static function htmlEntitiesDecode($value) {
		if( is_array($value) ) {
			$newArr = array();
			foreach($value as $key => $v) {
				$newArr[$key] = html_entity_decode($v, null, 'UTF-8');
			}
			return $newArr;
		}
		return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
	}

	public static function makeLink($text, $format1='<a href="\\0" rel="nofollow" title="">\\0</a>',
											$format2='<a href="http://\\2" title="" rel="nofollow">\\2</a>', 
											$format3='<a href="mailto:\\1" title="">\\1</a>') {
		// match protocol://address/path/
		$text = preg_replace("/[a-zA-Z]+:\\/\\/([.\\/-]?[a-zA-Z0-9_\\/-\\/&\\/%\\/?\\/=])*/is", $format1, $text);
		// match www.something
		$text = preg_replace("/(^|\\s)(www\\.([a-zA-Z0-9_\\/-\\/.])*)/is", $format2, $text);
		// match me@something.com
		return preg_replace('/([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})/is', $format3, $text);
	}

	public static function base64Encode($obj) {
		return base64_encode(serialize($obj));
	}

	public static function base64Decode($str, $defaultValue = null) {
		$req = base64_decode($str);
		if($req !== FALSE) {
			$req = unserialize($req);
			if($req) {
				return $req;
			}
		}
		return $defaultValue;
	}
}