<?php

namespace Pecee\Widget;

use Pecee\Str;

class WidgetMinify extends Widget
{

    protected function minifyJs(string $input): string
    {
        if (trim($input) === '') {
            return $input;
        }

        return preg_replace(
            array(
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3'
            ),
            $input);
    }

    protected function minify(string $html): string
    {
        if (str_contains($html, '</script>')) {
            $html = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function ($matches) {
                return '<script' . $matches[1] . '>' . $this->minifyJs($matches[2]) . '</script>';
            }, $html);
        }

        return Str::sanitizeHtml($html);
    }

    protected function getCacheDir(): string
    {
        return env('base_path') . 'cache/templates';
    }

    protected function cacheTemplate(string $template): string
    {
        $filename = str_replace([DIRECTORY_SEPARATOR, '/'], '_', $template);

        $minified = $this->getCacheDir() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($minified) === false || app()->getDebugEnabled() === true) {
            $contents = $this->minify(file_get_contents($template, FILE_USE_INCLUDE_PATH));
            file_put_contents($minified, $contents);
        }

        return $minified;
    }

    protected function setContentTemplate(?string $template): void
    {
        parent::setContentTemplate(
            $this->cacheTemplate($template)
        );
    }

    protected function setTemplate(?string $path, bool $relative = true): void
    {
        parent::setTemplate($path, $relative);

        if ($this->_template !== '') {
            $this->_template = $this->cacheTemplate($this->_template);
        }
    }

}