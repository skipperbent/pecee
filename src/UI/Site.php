<?php
namespace Pecee\UI;

use Pecee\UI\Html\Html;

class Site
{
    const SECTION_DEFAULT = 'default';

    protected $title;
    protected $description;
    protected $keywords = [];
    protected $header = [];
    protected $js = [];
    protected $css = [];
    protected $jsFilesWrapped = [];
    protected $cssFilesWrapped = [];

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function addWrappedJs($filename, $section = self::SECTION_DEFAULT)
    {
        if (isset($this->jsFilesWrapped[$section]) === false || in_array($filename, $this->jsFilesWrapped[$section], true) === false) {
            $this->jsFilesWrapped[$section][] = $filename;
        }

        return $this;
    }

    public function addWrappedCss($filename, $section = self::SECTION_DEFAULT)
    {
        if (isset($this->cssFilesWrapped[$section]) === false || in_array($filename, $this->cssFilesWrapped[$section], true) === false) {
            $this->cssFilesWrapped[$section][] = $filename;
        }

        return $this;
    }

    public function removeWrappedJs($filename, $section = self::SECTION_DEFAULT)
    {
        if (isset($this->jsFilesWrapped[$section]) === false || in_array($filename, $this->jsFilesWrapped[$section], true) === false) {
            $key = array_search($filename, $this->jsFilesWrapped, true);
            unset($this->jsFilesWrapped[$section][$key]);
        }

        return $this;
    }

    public function removeWrappedCss($filename, $section = self::SECTION_DEFAULT)
    {
        if (isset($this->cssFilesWrapped[$section]) === false || in_array($filename, $this->cssFilesWrapped[$section], true) === true) {
            $key = array_search($filename, $this->cssFilesWrapped, true);
            unset($this->cssFilesWrapped[$section][$key]);
        }

        return $this;
    }

    public function addCss($path, $section = self::SECTION_DEFAULT)
    {
        if (isset($this->css[$section]) === false || in_array($path, $this->css[$section], true) === false) {
            $this->css[$section][] = $path;
        }

        return $this;
    }

    public function addJs($path, $section = self::SECTION_DEFAULT)
    {
        if (isset($this->js[$section]) === false || in_array($path, $this->js[$section], true) === false) {
            $this->js[$section][] = $path;
        }

        return $this;
    }

    public function clearCss()
    {
        $this->cssFilesWrapped = [];

        return $this;
    }

    public function clearJs()
    {
        $this->jsFilesWrapped = [];

        return $this;
    }

    public function setKeywords(array $keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function addMeta(array $attributes)
    {
        return $this
            ->addHeader((new Html('meta'))
                ->setAttributes($attributes));
    }

    public function addHeader(Html $el)
    {
        $this->header[] = $el;

        return $el;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getJsFilesWrapped($section)
    {
        return isset($this->jsFilesWrapped[$section]) ? $this->jsFilesWrapped[$section] : [];
    }

    public function getCssFilesWrapped($section)
    {
        return isset($this->cssFilesWrapped[$section]) ? $this->cssFilesWrapped[$section] : [];
    }

    public function getJs($section = self::SECTION_DEFAULT)
    {
        return isset($this->js[$section]) ? $this->js[$section] : [];
    }

    public function getCss($section = self::SECTION_DEFAULT)
    {
        return isset($this->css[$section]) ? $this->css[$section] : [];
    }

}