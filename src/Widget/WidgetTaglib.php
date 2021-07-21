<?php

namespace Pecee\Widget;

use ErrorException;
use Exception;
use Pecee\UI\Phtml\Phtml;
use Pecee\UI\Taglib\TaglibJs;

abstract class WidgetTaglib extends Widget
{

    protected bool $injectDependencies = true;
    protected string $_pHtmlCacheDir;

    public function __construct()
    {
        parent::__construct();

        if ($this->injectDependencies === true) {
            $this->getSite()->addWrappedJs('pecee-widget.js');
        }

        app()->set(Phtml::SETTINGS_TAGLIB, [
            'js' => (new TaglibJs())->setNamespace('$fluent')->setRenderCallback(function ($content) {
                return $this->renderPhp($content);
            }),
        ]);

        $this->_pHtmlCacheDir = env('base_path') . 'cache/phtml';
    }

    protected function getHtmlParser(): Phtml
    {
        return new Phtml();
    }

    protected function renderPhp(string $content): string
    {
        ob_start();
        eval('?>' . $content);

        return ob_get_clean();
    }

    protected function renderFile($file): string
    {
        $cacheFile = $this->_pHtmlCacheDir . DIRECTORY_SEPARATOR . str_replace([DIRECTORY_SEPARATOR, '/'], '_', $file);

        if (is_file($cacheFile) === true) {
            if (app()->getDebugEnabled() === false) {
                return $this->renderPhp(file_get_contents($cacheFile));
            }

            unlink($cacheFile);
        }

        try {

            if ((is_dir($this->_pHtmlCacheDir) === false) && !mkdir($concurrentDirectory = $this->_pHtmlCacheDir, 0755, true) && !is_dir($concurrentDirectory)) {
                throw new ErrorException('Failed to create temp-cache directory');
            }

            debug('Parsing Phtml template');
            $pHtml = $this->getHtmlParser();
            $output = $pHtml->read(file_get_contents($file, FILE_USE_INCLUDE_PATH))->toPHP();
            debug('Finished parsing Phtml template');

            debug('Writing Phtml cache file');
            $handle = fopen($cacheFile, 'w+b+');
            fwrite($handle, $output);
            fclose($handle);
            debug('Finished writing Phtml cache file');

            $output = $this->renderPhp($output);

        } catch (Exception $e) {
            $output = $e->getMessage();
        }

        return $output;
    }

    protected function renderTemplate(): void
    {
        debug('START: rendering template: ' . $this->_template);

        if ($this->_template !== '') {
            $this->_contentHtml = $this->renderFile($this->_template);
        }

        debug('END: rendering template ' . $this->_template);
    }

    protected function renderContent(): void
    {
        $this->_contentHtml = $this->renderFile($this->_contentTemplate);
    }
}