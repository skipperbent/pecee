<?php

namespace Pecee\Traits\Widget;

use Pecee\UI\Phtml\Phtml;

trait TaglibRenderer
{

    protected bool $phpTagsEnabled = false;
    protected ?string $_template = null;

    abstract function setDependencies(): void;

    protected function getPhtmlCacheDir(): string
    {
        return env('base_path') . 'cache/phtml';
    }

    public function render(): ?string
    {
        $this->setDependencies();
        return parent::render();
    }

    protected function getHtmlParser(): Phtml
    {
        return new Phtml();
    }

    protected function renderPhp(string $content): string
    {
        if ($this->phpTagsEnabled) {
            // Add support for php{} tags
            preg_match_all('/php\{([^}]+)}/i', $content, $matches);

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
        $filename = str_replace([DIRECTORY_SEPARATOR, '/'], '_', $file);
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $filename;

        $hashFile = sprintf('%s/%s.md5', dirname($cacheFile), $filename);

        if (is_file($cacheFile) === true) {

            if (app()->getDebugEnabled() === false) {
                return $this->renderPhp(file_get_contents($cacheFile));
            } else {

                // Verify file hash
                if (is_file($hashFile)) {

                    $existingHash = file_get_contents($hashFile);
                    $currentHash = md5(file_get_contents($file, FILE_USE_INCLUDE_PATH));

                    if ($existingHash === $currentHash) {
                        return $this->renderPhp(file_get_contents($cacheFile));
                    }

                }
            }
        }

        try {
            if (is_dir($cacheDir) === false && mkdir($cacheDir, 0755, true) === false) {
                throw new \ErrorException('Failed to create temp-cache directory');
            }

            debug('taglib', 'Parsing Phtml template');
            $template = file_get_contents($file, FILE_USE_INCLUDE_PATH);
            $output = $this->getHtmlParser()->read($template)->toPHP();
            debug('taglib', 'Finished parsing Phtml template');

            debug('taglib', 'Writing Phtml cache file');
            $handle = fopen($cacheFile, 'w+b+');
            fwrite($handle, $output);
            fclose($handle);
            debug('taglib', 'Finished writing Phtml cache file');

            if (app()->getDebugEnabled()) {
                // Save file hash for comparison
                $handle = fopen($hashFile, 'w+b+');
                fwrite($handle, md5($template));
                fclose($handle);
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