<?php

namespace Pecee\IO;

use Pecee\Url;

class File
{

    /**
     * Creates temporary file and returns file-path.
     * Will automatically remove file upon shutdown.
     *
     * @param string $name
     * @param string|null $contents
     * @param bool $autoRemove
     * @return string Path to the temp-file created
     */
    public static function tmpFile(string $name, ?string $contents = null, bool $autoRemove = true): string
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid($name, false);

        if ($contents === null) {
            touch($file);
        } else {
            file_put_contents($file, $contents);
        }

        if ($autoRemove === false) {
            return $file;
        }

        register_shutdown_function(function () use ($file) {
            if (is_file($file) === true) {
                unlink($file);
            }
        });

        return $file;
    }

    /**
     * @param string $url
     * @return int
     */
    public static function remoteSize(string $url): int
    {
        $headers = array_change_key_case(get_headers($url, 1), CASE_LOWER);

        if (isset($headers['content-length'])) {
            return $headers['content-length'];
        }

        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);

        curl_exec($handle);
        $size = curl_getinfo($handle, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        return $size ?: -1;
    }

    public static function remoteExist(string $url): bool
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_exec($handle);

        $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($statusCode === 200) {
            return true;
        }

        curl_close($handle);

        return false;
    }

    public static function getRemoteMime(string $url): string
    {
        if (Url::isValid($url) === true) {
            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handle, CURLOPT_MAXREDIRS, 5);
            curl_setopt($handle, CURLOPT_HEADER, true);
            curl_setopt($handle, CURLOPT_NOBODY, true);

            curl_exec($handle);

            $mime = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
            curl_close($handle);

            return $mime;
        }

        throw new \ErrorException('Failed to parse mime-type');
    }

    public static function getExtension(string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return ($ext !== '') ? $ext : substr($path, strrpos('.', $path));
    }

    /**
     * Move file recursively
     * @param string $source
     * @param string $destination
     * @throws \ErrorException
     */
    public static function move(string $source, string $destination): void
    {
        if (is_dir($source) === true) {

            if (is_dir($destination) === false && mkdir($destination, 0755, true) === false) {
                throw new \ErrorException('Failed to create directory: ' . $destination);
            }

            $files = scandir($source, SCANDIR_SORT_NONE);
            foreach ($files as $file) {
                if (in_array($file, ['.', '..'], true) === false) {
                    static::move($source . '/' . $file, $destination . '/' . $file);
                }
            }
        } elseif (is_file($source) === true) {
            rename($source, $destination);
        }
    }
}