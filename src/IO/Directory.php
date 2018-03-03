<?php

namespace Pecee\IO;

class Directory
{

    /**
     * Copy directory recursive
     * @param string $source
     * @param string $destination
     * @param bool $overwrite
     * @throws \ErrorException
     */
    public static function copy($source, $destination, $overwrite = false)
    {
        $dir = opendir($source);

        if (is_dir($destination) === false && (mkdir($destination, 0755, true) === false || is_dir($destination) === false)) {
            throw new \ErrorException('Failed to create directory: ' . $destination);
        }

        while (($file = readdir($dir)) !== false) {
            if (\in_array($file, ['.', '..'], true) === false) {
                if (is_dir($source . '/' . $file)) {
                    static::copy($source . '/' . $file, $destination . '/' . $file);
                } else {
                    if ($overwrite === true || ($overwrite === false && is_file($destination . '/' . $file) === false)) {
                        copy($source . '/' . $file, $destination . '/' . $file);
                    }
                }
            }
        }

        closedir($dir);
    }

    public static function delete($path)
    {
        $files = glob($path . '/*');

        foreach ($files as $file) {
            is_dir($file) ? static::delete($file) : unlink($file);
        }

        rmdir($path);
    }

}