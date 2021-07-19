<?php
namespace Pecee\UI;

use Pecee\UI\Html\Html;

class Site
{
    public const SECTION_DEFAULT = 'default';

    protected ?string $title = null;
    protected ?string $description = null;
    protected array $keywords = [];
    protected array $header = [];
    protected array $js = [];
    protected array $css = [];
    protected array $jsFilesWrapped = [];
    protected array $cssFilesWrapped = [];

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function addWrappedJs(string $filename, string $section = self::SECTION_DEFAULT): self
    {
        if (isset($this->jsFilesWrapped[$section]) === false || in_array($filename, $this->jsFilesWrapped[$section], true) === false) {
            $this->jsFilesWrapped[$section][] = $filename;
        }

        return $this;
    }

    public function addWrappedCss(string $filename, string $section = self::SECTION_DEFAULT): self
    {
        if (isset($this->cssFilesWrapped[$section]) === false || in_array($filename, $this->cssFilesWrapped[$section], true) === false) {
            $this->cssFilesWrapped[$section][] = $filename;
        }

        return $this;
    }

    public function removeWrappedJs(string $filename, string $section = self::SECTION_DEFAULT): self
    {
        if (isset($this->jsFilesWrapped[$section]) === false || in_array($filename, $this->jsFilesWrapped[$section], true) === false) {
            $key = array_search($filename, $this->jsFilesWrapped, true);
            unset($this->jsFilesWrapped[$section][$key]);
        }

        return $this;
    }

    public function removeWrappedCss(string $filename, string $section = self::SECTION_DEFAULT): self
    {
        if (isset($this->cssFilesWrapped[$section]) === false || in_array($filename, $this->cssFilesWrapped[$section], true) === true) {
            $key = array_search($filename, $this->cssFilesWrapped, true);
            unset($this->cssFilesWrapped[$section][$key]);
        }

        return $this;
    }

    public function addCss(string $path, string $section = self::SECTION_DEFAULT): self
    {
        if (isset($this->css[$section]) === false || in_array($path, $this->css[$section], true) === false) {
            $this->css[$section][] = $path;
        }

        return $this;
    }

    public function addJs(string $path, string $section = self::SECTION_DEFAULT): self
    {
        if (isset($this->js[$section]) === false || in_array($path, $this->js[$section], true) === false) {
            $this->js[$section][] = $path;
        }

        return $this;
    }

    public function clearCss(): self
    {
        $this->cssFilesWrapped = [];

        return $this;
    }

    public function clearJs(): self
    {
        $this->jsFilesWrapped = [];

        return $this;
    }

    public function setKeywords(array $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function addMeta(array $attributes): Html
    {
        return $this
                ->addHeader((new Html('meta'))
                ->setClosingType(Html::CLOSE_TYPE_NONE)
                ->setAttributes($attributes));
    }

    public function addHeader(Html $el): Html
    {
        $this->header[] = $el;

        return $el;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function getJsFilesWrapped(string $section = self::SECTION_DEFAULT): array
    {
        return $this->jsFilesWrapped[$section] ?? [];
    }

    public function getCssFilesWrapped(string $section = self::SECTION_DEFAULT): array
    {
        return $this->cssFilesWrapped[$section] ?? [];
    }

    public function getJs(string $section = self::SECTION_DEFAULT): array
    {
        return $this->js[$section] ?? [];
    }

    public function getCss(string $section = self::SECTION_DEFAULT): array
    {
        return $this->css[$section] ?? [];
    }

}