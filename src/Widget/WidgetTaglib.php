<?php
namespace Pecee\Widget;

use Pecee\UI\Phtml\Phtml;

abstract class WidgetTaglib extends Widget
{

    protected $_pHtmlCacheDir;

    public function __construct()
    {
        parent::__construct();

        $this->getSite()->addWrappedJs('pecee-widget.js');
        $this->_pHtmlCacheDir = env('base_path') . 'cache/phtml';
    }

    public function render()
    {
        $this->renderContent();
        $this->renderTemplate();
        $this->_messages->clear();

        return $this->_contentHtml;
    }

    protected function renderPhp($content)
    {
        ob_start();
        eval('?>' . $content);
        $this->_contentHtml = ob_get_contents();
        ob_end_clean();
    }

    public function renderContent()
    {
        $cacheFile = $this->_pHtmlCacheDir . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, '_', $this->_contentTemplate);

        if (is_file($cacheFile)) {
            if (app()->getDebugEnabled() === false) {
                $this->renderPhp(file_get_contents($cacheFile));

                return;
            } else {
                unlink($cacheFile);
            }
        }

        try {

            if (!is_dir($this->_pHtmlCacheDir)) {
                if(mkdir($this->_pHtmlCacheDir, 0755, true) === false) {
                    throw new \ErrorException('Failed to create temp-cache directory');
                }
            }

            $this->renderPhp(file_get_contents($this->_contentTemplate, FILE_USE_INCLUDE_PATH));
            $pHtml = new Phtml();
            $output = $pHtml->read($this->_contentHtml)->toPHP();

            $this->_contentHtml = $output;

            $handle = fopen($cacheFile, 'w+b+');
            fwrite($handle, $this->_contentHtml);
            fclose($handle);

        } catch (\Exception $e) {
            $this->_contentHtml = $e->getMessage();
        }

    }
}