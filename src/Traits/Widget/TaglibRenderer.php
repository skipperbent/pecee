<?php

namespace Pecee\Traits\Widget;

use Pecee\UI\Phtml\Phtml;

trait TaglibRenderer
{

    protected $_pHtmlCacheDir;

    public function render()
    {
        $this->setTaglibJs();
        $this->_pHtmlCacheDir = env('base_path') . 'cache/phtml';

        $this->renderContent();
        $this->renderTemplate();
        $this->_messages->clear();

        return $this->_contentHtml;
    }

    public function setTaglibJs()
    {
        $this->getSite()->addWrappedJs('pecee-widget.js');
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
        $cacheFile = $this->_pHtmlCacheDir . DIRECTORY_SEPARATOR . str_replace([DIRECTORY_SEPARATOR, '/'], '_', $this->_contentTemplate);

        if (is_file($cacheFile) === true) {
            if (app()->getDebugEnabled() === false) {
                $this->_contentHtml = file_get_contents($cacheFile);
                return;
            } else {
                unlink($cacheFile);
            }
        }

        try {

            if ((is_dir($this->_pHtmlCacheDir) === false) && !mkdir($concurrentDirectory = $this->_pHtmlCacheDir, 0755, true) && !is_dir($concurrentDirectory)) {
                throw new \ErrorException('Failed to create temp-cache directory');
            }

            $this->renderPhp(file_get_contents($this->_contentTemplate, FILE_USE_INCLUDE_PATH));

            debug('Parsing Phtml template');
            $pHtml = new Phtml();
            $this->_contentHtml = $pHtml->read($this->_contentHtml)->toPHP();
            debug('Finished parsing Phtml template');

            debug('Writing Phtml cache file');
            $handle = fopen($cacheFile, 'w+b+');
            fwrite($handle, $this->_contentHtml);
            fclose($handle);
            debug('Finished writing Phtml cache file');

        } catch (\Exception $e) {
            $this->_contentHtml = $e->getMessage();
        }

    }
}