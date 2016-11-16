<?php

namespace Pecee\IO;

class Directory {

    public static function copy($source, $destination, $overwrite = false) {
        $dir = opendir($source);

        if(!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        while(($file = readdir($dir)) !== false) {
            if (!in_array($file, ['.', '..'])) {
                if (is_dir($source . '/' . $file)) {
                    static::copy($source . '/' . $file, $destination . '/' . $file);
                } else {
                    if($overwrite === true || $overwrite === false && is_file($destination . '/' . $file) === false) {
                        copy($source . '/' . $file, $destination . '/' . $file);
                    }
                }
            }
        }

        closedir($dir);
    }

}