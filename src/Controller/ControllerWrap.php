<?php
namespace Pecee\Controller;

use Pecee\UI\YuiCompressor\YuiCompressor;

class ControllerWrap
{
    protected $files;
    protected $tmpDir;
    protected $cacheFile;
    protected $contentType;
    protected $extension;
    protected $path;

    public function __construct()
    {
        // Set time limit
        set_time_limit(60);

        $this->tmpDir = env('base_path') . 'cache';
        $this->files = strpos(input()->get('files'), ',') ? explode(',', input()->get('files')) : [input()->get('files')];

        $this->cacheFile = md5(urldecode(input()->get('files'))) . '.' . $this->getExtension();

        if (is_dir($this->tmpDir) === false) {
            if(mkdir($this->tmpDir, 0755, true) === false) {
                throw new \ErrorException('Failed to create temp-directory');
            }
        }
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

    public function getHeader()
    {
        return 'application/javascript';
    }

    public function getExtension()
    {
        return 'js';
    }

    public function getPath()
    {
        return env('JS_PATH', 'public/js/');
    }

    public function wrap()
    {
        // Set headers
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

        if (!in_array('ob_gzhandler', ob_list_handlers())) {
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

                    $content = null;

                    $content = $this->loadFile($file);

                    /* Load content from framework */
                    if ($content === null) {
                        $content = $this->loadFile($this->path . $file);
                    }

                    // Try resources folder
                    if ($content !== null) {
                        $filePath = env('base_path') . '/resources/' . $this->getExtension() . '/' . $file;
                        if (is_file($filePath)) {
                            $content = file_get_contents($filePath);
                        }
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

        // Try module ressources
        if ($content === null && app()->hasModules() !== null) {
            foreach (app()->getModules() as $module) {
                $moduleFilePath = $module . DIRECTORY_SEPARATOR . $file;
                if (is_file($moduleFilePath)) {
                    $content = file_get_contents($moduleFilePath);
                    break;
                }
            }
        }

        return $content;
    }

    protected function cleanup()
    {
        $handle = opendir($this->tmpDir);

        while (false !== ($file = readdir($handle))) {
            if ($file === $this->cacheFile) {
                unlink($this->tmpDir . DIRECTORY_SEPARATOR . $file);
                break;
            }
        }

        closedir($handle);
    }

    protected function debugMode()
    {
        return env('DEBUG_FILE_WRAPPER', false);
    }

    protected function getTempFile()
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . $this->cacheFile;
    }

}