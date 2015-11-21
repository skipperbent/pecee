<?php
namespace Pecee\IO;

use Pecee\Url;

class File {

	public static function exists($path) {
		$inc = explode(PATH_SEPARATOR, get_include_path());
		foreach($inc as $prefix){
			if(substr($prefix,-1) == DIRECTORY_SEPARATOR)
				$prefix = substr($prefix,0,-1);
			$p = sprintf("%s%s%s", $prefix, DIRECTORY_SEPARATOR, $path);
			if(file_exists($p))
				return($p);
		}
		return false;
	}

	public static function remoteFilesize($url) {
		$headers = get_headers($url, 1);
		if (isset($headers['Content-Length'])) return $headers['Content-Length'];
		if (isset($headers['Content-length'])) return $headers['Content-length'];
		$c = curl_init();
		curl_setopt_array($c, array(CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => array('User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3'),));
		curl_exec($c);
		return curl_getinfo($c, CURLINFO_SIZE_DOWNLOAD);
	}

	public static function remoteExists($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		$result = curl_exec($curl);
		if ($result) {
			//if request was ok, check response code
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ($statusCode == 200) {
				return true;
			}
		}
		curl_close($curl);
		return false;
	}

	public static function getMime($file) {
		if(file_exists($file)) {
			return mime_content_type($file);
		} elseif(Url::isValid($file)) {
			$ch = curl_init($file);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true);

			curl_exec($ch);
			return curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		} else {
			$handle = finfo_open(FILEINFO_MIME);
			$mime = finfo_file($handle, $file);
			finfo_close($handle);
			return $mime;
		}
		throw new \ErrorException('Unable to determinate mimetime');
	}

	public static function getExtension($path) {
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if($ext == '') {
			$ext = substr($path, strrpos('.', $path));
		}
		return $ext;
	}

	public static function move($source, $destination) {
		if(is_dir($source)) {
			if(!is_dir($destination)) {
				mkdir($destination, 0777, true);
			}
			$files = scandir($source);
			foreach($files as $file) {
				if($file != "." && $file != "..") {
					self::move("$source/$file", "$destination/$file");
				}
			}
		} elseif(file_exists($source)) {
			copy($source, $destination);
		}
	}
}