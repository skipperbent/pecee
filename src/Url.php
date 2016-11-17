<?php
namespace Pecee;

class Url {

	public static function getHost($url) {
		return str_ireplace('www.', '', parse_url($url, PHP_URL_HOST));
	}

	public static function hasParams($url) {
		return (strpos($url, '?') > -1);
	}

	public static function paramsToArray($query) {
		$output = array();
		if($query[0] === '?') {
			$query = substr($query, 1);
		}
		parse_str($query, $output);
		return $output;
	}

	public static function getParamsSeparator($url) {
		return (strpos($url, '?') > -1) ? '&' : '?';
	}

	public static function arrayToParams(array $getParams = null, $includeEmpty = true) {
		if(is_array($getParams) && count($getParams) > 0) {
			foreach($getParams as $key=>$val) {
				if(!empty($val) || empty($val) && $includeEmpty) {
					$getParams[$key] = $key . '=' . $val;
				}
			}
			return join('&', $getParams);
		}
		return '';
	}

	public static function isValid($url) {
		return (preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url) === 1);
	}

	public static function isValidHostname($hostname) {
		return (preg_match('/^ (?: [a-z0-9] (?:[a-z0-9\\-]* [a-z0-9])? \\. )*  #Subdomains
   							[a-z0-9] (?:[a-z0-9\\-]* [a-z0-9])?            #Domain
   							\\. [a-z]{2,6} $                               #Top-level domain
							/ix', $hostname) === 1);
	}

	public static function urlEncodeString($string, $separator = '-', $maxLength = 50) {
		if($maxLength !== null && strlen($string) > $maxLength) {
			$string = substr($string, 0, $maxLength);
		}

		$searchMap = [
		    'æ' => 'ae',
            'ø' => 'o',
            'å' => 'a',
            ' ' => $separator
        ];

        $string = str_ireplace(array_keys($searchMap), $searchMap, strtolower($string));
		return preg_replace('/[^\w\ \+\&'. join('', $searchMap) .']/i', '', $string);
	}

	public static function isSecure($url) {
		return (strtolower(parse_url($url, PHP_URL_SCHEME)) === 'https');
	}
}