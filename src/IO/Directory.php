<?php

namespace Pecee\IO;

class Directory {

    public static function copy($source, $destination, $overwrite = false) {
        $dir = opendir($source);
        if(!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        while(($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if ( is_dir($source . '/' . $file) ) {
                    self::copy($source . '/' . $file, $destination . '/' . $file);
                } else {
                    if($overwrite === false && file_exists($destination . '/' . $file) || $overwrite === true) {
                        copy($source . '/' . $file, $destination . '/' . $file);
                    }
                }
            }
        }
        closedir($dir);
    }

    public static function normalize($path) {
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

}