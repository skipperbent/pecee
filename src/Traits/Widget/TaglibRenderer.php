<?php

namespace Pecee\Traits\Widget;

use Pecee\UI\Phtml\Phtml;

trait TaglibRenderer
{

    public function setJsDependencies()
    {
        $this->getSite()->addWrappedJs('js/pecee-widget.js');
    }

    protected function getPhtmlCacheDir(): string
    {
        return env('base_path') . 'cache/phtml';
    }

    public function render(): ?string
    {
        $this->setJsDependencies();

        if ($this->_template === null) {
            $this->setTemplate('Default.php');
        }

        if ($this->_contentTemplate === null) {
            $this->setContentTemplate($this->getTemplatePath());
        }

        $this->setInputValues();

        // Trigger postback event
        if (request()->getMethod() === 'post') {
            $this->onPostBack();
        }

        // Trigger onLoad event
        $this->onLoad();

        $this->renderContent();
        $this->renderTemplate();

        $this->sessionMessage()->clear();

        debug('taglib', 'END WIDGET: %s', static::class);

        return $this->_contentHtml;
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
        $cacheDir = $this->getPhtmlCacheDir();
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . str_replace([DIRECTORY_SEPARATOR, '/'], '_', $file);
        $output = '';

        if (is_file($cacheFile) === true) {
            if (app()->getDebugEnabled() === false) {
                return (string)$this->renderPhp(file_get_contents($cacheFile));
            } else {
                unlink($cacheFile);
            }
        }

        try {

            if (is_dir($cacheDir) === false) {
                if (mkdir($cacheDir, 0755, true) === false) {
                    throw new \ErrorException('Failed to create temp-cache directory');
                }
            }

            debug('taglib', 'Parsing Phtml template');
            $pHtml = $this->getHtmlParser();
            $output = $pHtml->read(file_get_contents($file, FILE_USE_INCLUDE_PATH))->toPHP();
            debug('taglib', 'Finished parsing Phtml template');

            debug('taglib', 'Writing Phtml cache file');
            $handle = fopen($cacheFile, 'w+b+');
            fwrite($handle, $output);
            fclose($handle);
            debug('taglib', 'Finished writing Phtml cache file');

            $output = $this->renderPhp($output);

        } catch (\Exception $e) {
            $output = $e->getMessage();
        }

        return $output;
    }

    protected function renderTemplate(): void
    {
        debug('taglib', 'START: rendering template: %s', $this->_template);

        if ($this->_template !== '') {
            $this->_contentHtml = $this->renderFile($this->_template);
        }

        debug('taglib', 'END: rendering template %s', $this->_template);
    }

    protected function renderContent(): void
    {
        $this->_contentHtml = $this->renderFile($this->_contentTemplate);
    }
}