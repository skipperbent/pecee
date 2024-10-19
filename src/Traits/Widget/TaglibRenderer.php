<?php

namespace Pecee\Traits\Widget;

use Pecee\UI\Phtml\Phtml;

trait TaglibRenderer
{

    protected bool $phpTagsEnabled = false;

    public function setJsDependencies(): void
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

        debug('taglib', 'END WIDGET: %s', static::class);

        return $this->onRender($this->_contentHtml);
    }

    protected function getHtmlParser(): Phtml
    {
        return new Phtml();
    }

    protected function renderPhp(string $content): string
    {
        if ($this->phpTagsEnabled) {
            // Add support for php{} tags
            preg_match_all('/php\{([^}]+)\}/is', $content, $matches);

            if (count($matches[0])) {
                foreach ($matches[0] as $index => $match) {
                    $output = str_replace(["\n", "\t", chr(13)], '', str_replace('"', '\"', eval('return ' . $matches[1][$index] . ';')));
                    $content = str_replace($match, $output, $content);
                }
            }
        }

        ob_start();
        eval('?>' . $content);
        return ob_get_clean();
    }

    protected function renderFile($file): string
    {
        $cacheDir = $this->getPhtmlCacheDir();
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . str_replace([DIRECTORY_SEPARATOR, '/'], '_', $file);

        if (is_file($cacheFile) === true && app()->getDebugEnabled() === false) {
            return $this->renderPhp(file_get_contents($cacheFile));
        }

        try {
            if (is_dir($cacheDir) === false && mkdir($cacheDir, 0755, true) === false) {
                throw new \ErrorException('Failed to create temp-cache directory');
            }

            debug('taglib', 'Parsing Phtml template');
            $pHtml = $this->getHtmlParser();
            $output = $pHtml->read(file_get_contents($file, FILE_USE_INCLUDE_PATH))->toPHP();
            debug('taglib', 'Finished parsing Phtml template');

            if (app()->getDebugEnabled() === false) {

                debug('taglib', 'Writing Phtml cache file');
                $handle = fopen($cacheFile, 'w+b+');
                fwrite($handle, $output);
                fclose($handle);
                debug('taglib', 'Finished writing Phtml cache file');

            }

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
        if ($this->_contentHtml === null && $this->_contentTemplate !== null && $this->_contentTemplate !== '') {
            $this->_contentHtml = $this->renderFile($this->_contentTemplate);
        }
    }

    public function setIsPhpTagsEnabled(bool $value): self
    {
        $this->phpTagsEnabled = $value;
        return $this;
    }
}