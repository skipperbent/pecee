<?php
namespace Pecee\Widget;

use Pecee\UI\Phtml\Phtml;

abstract class WidgetTaglib extends Widget {

    protected $cacheDir;

    public function __construct() {
        parent::__construct();

        $this->getSite()->addWrappedJs('pecee-widget.js');

        $this->cacheDir = $_ENV['base_path'] . 'cache';
    }

    public function render()  {
        $this->renderContent();
        $this->renderTemplate();
        $this->_messages->clear();
        return $this->_contentHtml;
    }

    protected function renderPhp($content) {
        ob_start();
        eval('?>'. $content);
        $this->_contentHtml = ob_get_contents();
        ob_end_clean();
    }

    public function renderContent() {
        $cacheDir = $this->cacheDir . DIRECTORY_SEPARATOR . 'phtml';
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, '_', $this->_contentTemplate);

        $cacheExists = file_exists($cacheFile);

        if($cacheExists && !$this->getSite()->getDebug()) {
            $this->renderPhp(file_get_contents($cacheFile));
        } else {

            if($cacheExists && $this->getSite()->getDebug()) {
                unlink($cacheFile);
            }

            $phtml = new Phtml();
            $error = false;
            try {
                $this->renderPhp(file_get_contents($this->_contentTemplate, FILE_USE_INCLUDE_PATH));
                $this->_contentHtml = $phtml->read($this->_contentHtml)->toPHP();
            } catch(\Exception $e) {
                $this->_contentHtml = $e->getMessage();
                $error = true;
            }

            if(!$error) {
                if(!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0777, true);
                }

                $handle = fopen($cacheFile, 'w+');
                fwrite($handle, $this->_contentHtml);
                fclose($handle);
            }
        }
    }
}