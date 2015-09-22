<?php
namespace Pecee\Widget;
use Pecee\File;

abstract class WidgetTaglib extends \Pecee\Widget {
	public function __construct() {
		parent::__construct();
	}

    public function render()  {
        $this->renderContent();
        $this->renderTemplate();
        $this->_messages->clear();
        return \Pecee\String::getFirstOrDefault($this->_contentHtml, '');
    }

    public function renderContent() {
        if($this->getSite()->getCacheEnabled()) {
            $cacheDir = $this->getSite()->getCacheDir() . DIRECTORY_SEPARATOR . 'phtml';
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, '_', $this->_contentTemplate);

            $cacheExists = file_exists($cacheFile);

            if($cacheExists && !$this->getSite()->getDebug()) {
                $this->_contentHtml = file_get_contents($cacheFile);
            } else {

                if($cacheExists && $this->getSite()->getDebug()) {
                    unlink($cacheFile);
                }

                $phtml=new \Pecee\UI\Phtml\Phtml();
                $error = false;
                try {
                    $this->_contentHtml = $phtml->read(file_get_contents($this->_contentTemplate, FILE_USE_INCLUDE_PATH))->toPHP();
                } catch(\Exception $e) {
                    $this->_contentHtml = $e->getMessage();
                    $error = true;
                }

                if(!$error) {
                    if(!is_dir($cacheDir)) {
                        File::CreatePath($cacheDir);
                    }

                    $handle = fopen($cacheFile, 'w+');
                    fwrite($handle, $this->_contentHtml);
                    fclose($handle);
                }
            }
        }

        ob_start();
        eval('?>'. $this->_contentHtml);
        $this->_contentHtml = ob_get_contents();
        ob_end_clean();
    }
}