<?php
namespace Pecee\Controller\File;

abstract class FileAbstract {

    protected $files;
    protected $tmpDir;
    protected $cacheFile;

    public function __construct() {

        $this->tmpDir = $_ENV['base_path'] . DIRECTORY_SEPARATOR . 'cache';
        $this->files = (strpos(input()->get('files'), ',')) ? explode(',', input()->get('files')) : array(input()->get('files'));
        $this->cacheFile = sprintf('%s.%s', md5(urldecode(input()->get('files'))), $this->getExtension());

        if(!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }

    }

    public function wrap() {

        // Set time limit
        set_time_limit(60);

        // Set headers
        response()->headers([
            'Content-type: '. $this->getHeader(),
            'Charset: ' . request()->site->getCharset(),
        ]);

        $exists = is_file($this->getTempFile());

        if(!$this->debugMode() && $exists) {
            $md5 = md5_file($this->getTempFile());

            if (!in_array('ob_gzhandler', ob_list_handlers())) {
                ob_start('ob_gzhandler');
            }

            // Set headers
            response()->cache($md5, filemtime($this->getTempFile()));
        } else {
            // Clear existing files...
            $this->cleanup();
            $exists = false;
        }

        if($exists === false) {
            $this->saveTempFile();
        }

        echo file_get_contents($this->getTempFile());
    }

    protected function saveTempFile() {

        if(count($this->files)) {
            $handle = fopen($this->getTempFile(), 'w+');
            if($handle !== false) {
                for($i = 0; $i < count($this->files); $i++) {
                    $file = $this->files[$i];
                    $content = false;
                    $filePath = $this->getPath() . $file;

                    // Try default location
                    if(stream_resolve_include_path($filePath) !== false) {
                        $content = file_get_contents($filePath, FILE_USE_INCLUDE_PATH);
                    }

                    // Try module ressources
                    if($content === false && request()->modules !== null) {
                        foreach(request()->modules->getList() as $module) {
                            $moduleFilePath = $module . DIRECTORY_SEPARATOR . $filePath;
                            if(is_file($moduleFilePath)) {
                                $content = file_get_contents($moduleFilePath);
                                break;
                            }
                        }
                    }

                    // Try resources folder
                    if($content !== false) {
                        $filePath = $_ENV['base_path'] . '/resources/' . $this->getExtension() . '/' . $file;
                        if(is_file($filePath)) {
                            $content = file_get_contents($filePath);
                        }
                    }

                    if($content) {

                        $this->processContent($content);

                        $buffer = sprintf('/* %s */', $file) . chr(10) . $content;
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

    protected function cleanup() {
        $handle = opendir($this->tmpDir);

        while (false !== ($file = readdir($handle))) {
            if($file === $this->cacheFile) {
                unlink($this->tmpDir . DIRECTORY_SEPARATOR . $file);
                break;
            }
        }

        closedir($handle);
    }

    protected function debugMode() {
        return env('DEBUG_FILE_WRAPPER', false);
    }

    protected function getTempFile() {
        return $this->tmpDir . DIRECTORY_SEPARATOR . $this->cacheFile;
    }

    abstract function getExtension();
    abstract function getHeader();
    abstract function getPath();
    abstract function processContent(&$content);

}