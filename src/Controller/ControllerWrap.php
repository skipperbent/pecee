<?php

namespace Pecee\Controller;

use Pecee\Boolean;
use Pecee\UI\YuiCompressor\YuiCompressor;

class ControllerWrap
{
    /**
     * Execution time limit in seconds
     * @var int
     */
    protected $timeLimit = 60;
    protected $files;
    protected $cacheDirectory;
    protected $fileIdentifier;
    protected $contentType;
    protected $extension;
    protected $path;

    public function __construct()
    {
        set_time_limit($this->timeLimit);

        $this->cacheDirectory = env('base_path') . 'cache';
        $this->files = strpos(input('files'), ',') ? explode(',', input('files')) : [input('files')];
        $this->fileIdentifier = md5(urldecode(input('files')));
        $this->path = env('JS_PATH', 'public/js/');
    }

    public function js()
    {
        $this->extension = 'js';
        $this->contentType = 'application/javascript';
        $this->path = env('JS_WRAP_PATH', 'public/js/');

        $this->wrap();
    }

    public function css()
    {
        $this->extension = 'css';
        $this->contentType = 'text/css';
        $this->path = env('CSS_WRAP_PATH', 'public/css/');

        $this->wrap();
    }

    protected function getCacheDirectory()
    {
        return $this->cacheDirectory;
    }

    protected function getFiles()
    {
        return $this->files;
    }

    protected function getFileIdentifier()
    {
        return $this->fileIdentifier;
    }

    public function getHeader()
    {
        return 'application/javascript';
    }

    public function getExtension()
    {
        return $this->extension;
    }

    protected function getPath()
    {
        return $this->path;
    }

    public function wrap()
    {
        if (is_dir($this->getCacheDirectory()) === false && mkdir($this->getCacheDirectory(), 0755, true) === false) {
            throw new \ErrorException('Failed to create temp-directory');
        }

        response()->headers([
            'Content-type: ' . $this->contentType,
            'Charset: ' . app()->getCharset(),
        ]);

        $exists = is_file($this->getTempFile());

        if ($exists === true && $this->debugMode() === false) {
            $md5 = md5_file($this->getTempFile());

            // Set headers
            response()->cache($md5, filemtime($this->getTempFile()));
        } else {
            // Clear existing files...
            $this->cleanup();
            $exists = false;
        }

        if ($exists === false) {
            $this->saveTempFile();
        }

        if (in_array('ob_gzhandler', ob_list_handlers(), true) === false) {
            ob_start('ob_gzhandler');
        }

        echo file_get_contents($this->getTempFile());
    }

    protected function saveTempFile()
    {

        if (count($this->files)) {

            $handle = fopen($this->getTempFile(), 'w+b+');

            if ($handle !== false) {

                for ($i = 0, $maxFiles = count($this->files); $i < $maxFiles; $i++) {

                    $file = $this->files[$i];

                    $content = $this->loadFile($file);

                    /* Load content from framework */
                    if ($content === null) {
                        $content = $this->loadFile($this->getPath() . $file);
                    }

                    if ($content !== null) {

                        if (env('MINIFY_JS', false)) {
                            $compressor = new YuiCompressor();
                            $compressor->addContent($this->extension, $content);
                            $output = $compressor->minify(true);

                            if ($output->minified && $output->minified !== '') {
                                $content = $output->minified;
                            }
                        }

                        $buffer = '/* ' . $file . ' */' . chr(10) . $content;
                        fwrite($handle, $buffer);

                        // Unset buffer
                        $buffer = null;
                    }
                }

                fclose($handle);
                chmod($this->getTempFile(), 0755);

            }
        }
    }

    protected function loadFile($file)
    {
        $content = null;

        // Try default location
        if (stream_resolve_include_path($file) !== false) {
            $content = file_get_contents($file, FILE_USE_INCLUDE_PATH);
        }

        // Try module resources
        if ($content === null && app()->hasModules() !== null) {
            foreach (app()->getModules() as $module) {
                $moduleFilePath = $module . DIRECTORY_SEPARATOR . $file;
                if (is_file($moduleFilePath)) {
                    $content = file_get_contents($moduleFilePath);
                    break;
                }
            }
        }

        // Try resources folder
        if ($content === null) {
            $file = strtolower($this->getExtension()) . '/' . $file;
            if (stream_resolve_include_path($file) !== false) {
                return file_get_contents($file, FILE_USE_INCLUDE_PATH);
            }
        }

        return $content;
    }

    protected function cleanup()
    {
        $handle = opendir($this->getCacheDirectory());

        while (false !== ($file = readdir($handle))) {
            if ($file === $this->getFileIdentifier()) {
                unlink($this->getCacheDirectory() . DIRECTORY_SEPARATOR . $file);
                break;
            }
        }

        closedir($handle);
    }

    protected function debugMode()
    {
        return Boolean::parse(env('DEBUG_FILE_WRAPPER', false));
    }

    protected function getTempFile()
    {
        return $this->getCacheDirectory() . DIRECTORY_SEPARATOR . $this->getFileIdentifier() . '.' . $this->getExtension();
    }

}