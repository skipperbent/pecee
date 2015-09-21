<?php
namespace Pecee\Controller\File;
use Pecee\File;
use Pecee\Registry;
use Pecee\UI\Site;

abstract class FileAbstract extends Controller {
    protected $files;
    protected $cacheDate;
    protected $type;
    protected $tmpDir;

    const TYPE_JAVASCRIPT = 'js';
    const TYPE_CSS = 'css';
    public static $types=array(self::TYPE_JAVASCRIPT, self::TYPE_CSS);

    public function __construct($type) {

        parent::__construct();

        if(!in_array($type, self::$types)) {
            throw new \InvalidArgumentException(sprintf('Unknown type, must be one of the following: %s', join(', ', self::$types)));
        }
        $this->type = $type;

        $this->tmpDir = dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . 'cache';
    }

    public function wrapView($files = null) {
        set_time_limit(60);
        $this->files = $files;
        $this->cacheDate = $this->getParam('_', '');
        header('Content-type: '.$this->getHeader());
        header('Charset: ' . \Pecee\UI\Site::getInstance()->getCharset());
        header('Cache-Control: must-revalidate');
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() + 9600) . ' GMT');

        if(!in_array('ob_gzhandler', ob_list_handlers())) {
            ob_start ("ob_gzhandler");
        }

        if(isset($_GET['__clearcache']) && Site::getInstance()->hasAdminIp() && is_dir($this->tmpDir)) {
            $handle = opendir($this->tmpDir);
            while (false !== ($file = readdir($handle))) {
                if($file == (md5($this->files . $this->cacheDate) . '.' . $this->type)) {
                    unlink($this->tmpDir . DIRECTORY_SEPARATOR . $file);
                }
            }
            closedir($handle);
        }

        if(!file_exists($this->getTempFile()) || Registry::getInstance()->get('DisableFileWrapperCache', false)) {
            $this->saveTempFile();
        }
        echo file_get_contents($this->getTempFile(), FILE_USE_INCLUDE_PATH);
    }
    protected function saveTempFile() {
        if($this->files) {
            $files = (strpos($this->files, ',')) ? @explode(',', $this->files) : array($this->files);
            if(count($files) > 0) {
                /* Begin wrapping */
                if(!is_dir($this->tmpDir)) {
                    File::CreatePath($this->tmpDir);
                }
                $handle = fopen($this->getTempFile(), 'w+', FILE_USE_INCLUDE_PATH);
                if($handle) {
                    foreach($files as $index=>$file) {
                        $content = null;
                        $filepath = 'www/' . $this->getPath() . $file;

                        if(stream_resolve_include_path($filepath) !== false) {
                            $content = file_get_contents($filepath, FILE_USE_INCLUDE_PATH);
                        } else {
                            $modules = \Pecee\Module::getInstance()->getModules();
                            if($modules) {
                                foreach($modules as $module) {
                                    $moduleFilePath = $module . DIRECTORY_SEPARATOR . $filepath;
                                    if(file_exists($moduleFilePath)) {
                                        $content = file_get_contents($moduleFilePath);
                                        break;
                                    }
                                }
                            }
                        }

                        if(!$content) {
                            // Try ressources folder
                            $filepath = dirname(dirname(dirname(__DIR__))) . '/resources/' . $this->getPath() . $file;
                            if(file_exists($filepath)) {
                                $content = file_get_contents($filepath, FILE_USE_INCLUDE_PATH);
                            }
                        }

                        if($content) {
                            if($this->type==self::TYPE_JAVASCRIPT && !$this->debugMode()) {
                                $content=\Pecee\Web\Minify\JSMin\JSMin::minify($content);
                            }

                            if($this->type==self::TYPE_CSS && !$this->debugMode()) {
                                $content=\Pecee\Web\Minify\CSSMin::process($content);
                            }

                            $buffer = '/* '.strtoupper($this->type).': ' . $file . ' */';
                            $buffer.= ($this->debugMode()) ? $content : \Pecee\String::removeTabs($content);

                            if( $index < count($files)-1 ) {
                                $buffer .= str_repeat(chr(10),2);
                            }
                            fwrite($handle, $buffer);
                        }
                    }
                    fclose($handle);
                    chmod($this->getTempFile(), 0777);
                }
            }
        }
    }
    protected function debugMode() {
        return (strtolower($this->getParam('__debug')) == 'true' && Site::getInstance()->hasAdminIp());
    }
    protected function getHeader() {
        switch($this->type) {
            case self::TYPE_CSS:
                return 'text/css';
                break;
            case self::TYPE_JAVASCRIPT:
                return 'application/javascript';
                break;
        }
        return '';
    }
    protected function getPath() {
        switch($this->type) {
            case self::TYPE_JAVASCRIPT:
                return \Pecee\UI\Site::getInstance()->getJsPath();
                break;
            case self::TYPE_CSS:
                return \Pecee\UI\Site::getInstance()->getCssPath();
                break;
        }
        return '';
    }
    protected function getTempFile() {
        return sprintf('%s.%s', $this->tmpDir.DIRECTORY_SEPARATOR.md5($this->files), $this->type);
    }
}