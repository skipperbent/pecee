<?php
namespace Pecee\UI;

use Pecee\UI\Html\Html;

class Site {

	const SECTION_DEFAULT = 'default';

	protected $title;
	protected $description;
	protected $keywords = array();
	protected $header = array();
	protected $js = array();
	protected $css = array();
	protected $jsFilesWrapped = array();
	protected $cssFilesWrapped = array();

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
        return $this;
	}

	public function getDescription() {
		return $this->description;
	}

	public function setDescription($description) {
		$this->description = $description;
        return $this;
	}

	public function addWrappedJs($filename, $section = self::SECTION_DEFAULT) {
		if(!in_array($filename, $this->jsFilesWrapped)) {
			$this->jsFilesWrapped[$section][] = $filename;
		}
        return $this;
	}

	public function addWrappedCss($filename, $section = self::SECTION_DEFAULT) {
		if(!in_array($filename, $this->cssFilesWrapped)) {
			$this->cssFilesWrapped[$section][] = $filename;
		}
        return $this;
	}

	public function removeWrappedJs($filename, $section = self::SECTION_DEFAULT) {
		if(in_array($filename, $this->jsFilesWrapped)) {
			$key = array_search($filename, $this->jsFilesWrapped);
			unset($this->jsFilesWrapped[$section][$key]);
		}
        return $this;
	}

	public function removeWrappedCss($filename, $section = self::SECTION_DEFAULT) {
		if(in_array($filename, $this->cssFilesWrapped)) {
			$key = array_search($filename, $this->cssFilesWrapped);
			unset($this->cssFilesWrapped[$section][$key]);
		}
        return $this;
	}

	public function addCss($path, $section = self::SECTION_DEFAULT) {
		if(!in_array($path, $this->css)) {
			$this->css[$section][] = $path;
		}
		return $this;
	}

	public function addJs($path, $section = self::SECTION_DEFAULT) {
		if(!in_array($path, $this->js)) {
			$this->js[$section][] = $path;
		}
		return $this;
	}

	public function clearCss() {
		$this->cssFilesWrapped = array();
        return $this;
	}

	public function clearJs() {
		$this->jsFilesWrapped = array();
        return $this;
	}

	public function setKeywords(array $keywords) {
		$this->keywords = $keywords;
		return $this;
	}

	public function getKeywords() {
		return $this->keywords;
	}

	public function addMeta(array $attributes) {
		return $this
			->addHeader((new Html('meta'))
			->setClosingType(Html::CLOSE_TYPE_SELF)
			->setAttributes($attributes));
	}

	public function addHeader(Html $el) {
		$this->header[] = $el;
		return $el;
	}

	public function getHeader() {
		return $this->header;
	}

	public function getJsFilesWrapped($section) {
		return (isset($this->jsFilesWrapped[$section]) ? $this->jsFilesWrapped[$section] : array());
	}

	public function getCssFilesWrapped($section) {
		return (isset($this->cssFilesWrapped[$section]) ? $this->cssFilesWrapped[$section] : array());
	}

	public function getJs($section = self::SECTION_DEFAULT) {
		return (isset($this->js[$section]) ? $this->js[$section] : array());
	}

	public function getCss($section = self::SECTION_DEFAULT) {
		return (isset($this->css[$section]) ? $this->css[$section] : array());
	}

}