<?php
namespace Pecee;
class Guid {

	public static function create($separator = false) {
		if(function_exists('com_create_guid')) {
	        $guid = trim(com_create_guid(), '{}');
	        return (!$separator) ? str_replace('-', '', $guid) : $guid;
	    }
	    $pattern = (!$separator) ? '%04X%04X%04X%04X%04X%04X%04X%04X' : '%04X%04X-%04X-%04X-%04X-%04X%04X%04X';
	    return sprintf($pattern,
    		mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535),
    		mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535),
    		mt_rand(0, 65535), mt_rand(0, 65535));
	}

}