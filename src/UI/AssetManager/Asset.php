<?php
namespace Pecee\UI\AssetManager;

abstract class Asset implements IAsset {

    protected $files = array();
    protected $filename;
    protected $destinationPath;

    public function __construct($filename) {
        $this->filename = $filename;
    }

    /**
     * Add another file
     * @param string $sourceFile
     * @return static $this
     */
    public function mix($sourceFile) {
        $this->files[] = $sourceFile;
        return $this;
    }

    /**
     * Find local resource from relative, absolute or from within modules
     * and retrieve the contents of the file.
     *
     * @param string $filePath
     * @return bool|string
     */
    protected function getResourceContent($filePath) {
        $content = false;

        if(is_file($filePath)) {
            $content = file_get_contents($filePath);
        }

        // Try default location
        if(stream_resolve_include_path($filePath) !== false) {
            $content = file_get_contents($filePath, FILE_USE_INCLUDE_PATH);
        }

        // Try module resources
        if($content === false && request()->modules !== null) {
            foreach(request()->modules->getList() as $module) {
                $moduleFilePath = $module . DIRECTORY_SEPARATOR . $filePath;
                if(is_file($moduleFilePath)) {
                    $content = file_get_contents($moduleFilePath);
                    break;
                }
            }
        }

        return $content;
    }

    public function build() {

        if(!is_dir($this->destinationPath)) {
            mkdir($this->destinationPath, 0755);
        }

        $handle = fopen($this->destinationPath . '/' . $this->filename, 'w+');

        foreach($this->files as $file) {

            $contents = $this->getResourceContent($file);

            if($contents === false) {
                continue;
            }

            $this->processFile($file, $contents);

            $buffer = sprintf('/* %s */', $file) . chr(10) . $contents;
            fwrite($handle, $buffer);

            $buffer = null;

        }

        fclose($handle);

    }

    /**
     * @param string $path
     * @return static $this
     */
    public function setDestinationPath($path) {
        $this->destinationPath = rtrim($path, '/');
        return $this;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }

    public function getFilename() {
        return $this->filename;
    }

    abstract protected function processFile($file, &$contents);

}