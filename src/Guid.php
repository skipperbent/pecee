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

	public static function encrypt($dataInput, $key){
		$td = mcrypt_module_open(MCRYPT_CAST_256, '', 'ecb', '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$encrypted_data = mcrypt_generic($td, $dataInput);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$encoded_64 = base64_encode($encrypted_data);
		return $encoded_64;
	}

	public static function decrypt($encoded64, $key){
		$td = mcrypt_module_open(MCRYPT_CAST_256, '', 'ecb', '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$decrypted_data = mdecrypt_generic($td, base64_decode($encoded64));
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $decrypted_data;
	}

	/**
	 * Creates an random password, with a given length.
	 * @param int $length
	 * @return string
	 * @throws \Exception
	 */
	public static function createRandomPassword($length) {
		$chars = "ABCDEFGHIJKLMNOPQRSTUVXYXW023456789";
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '' ;
		while ($i <= $length) {
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		return $pass;
	}

}