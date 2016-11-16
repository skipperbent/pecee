<?php
namespace Pecee\UI;

use Pecee\UI\Html\Html;
use Pecee\UI\Html\HtmlMeta;

class Site {

	const SECTION_DEFAULT = 'default';

	const DOCTYPE_HTML_5 = '<!DOCTYPE html>';
	const DOCTYPE_XHTML_DEFAULT = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	const DOCTYPE_XHTML_STRICT = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

	const CHARSET_UTF8 = 'UTF-8';

	// Settings
    public static $docTypes = [
        self::DOCTYPE_HTML_5,
        self::DOCTYPE_XHTML_STRICT,
        self::DOCTYPE_XHTML_DEFAULT,
    ];

	protected $docType = self::DOCTYPE_HTML_5;
	protected $charset = self::CHARSET_UTF8;
	protected $title;
	protected $description;
	protected $keywords = array();
	protected $header = array();
	protected $js = array();
	protected $css = array();
	protected $jsFilesWrapped = array();
	protected $cssFilesWrapped = array();
	protected $adminIps = [
	    '127.0.0.1',
        '::1',
    ];

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

	public function setDocType($docType) {
		if(!in_array($docType, static::$docTypes)) {
            throw new \InvalidArgumentException('Invalid or unsupported docType.');
        }
		$this->docType = $docType;
        return $this;
	}

	public function getDocType() {
		return $this->docType;
	}

	public function getCharset() {
		return $this->charset;
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

	public function addMeta($name, $content) {
		return $this->addHeader((new HtmlMeta($content))->attr('name', $name));
	}

	public function addHeader(Html $el) {
		$this->header[] = $el;
		return $this;
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

	public function getHeader() {
		return $this->header;
	}

	/**
	 * @return array
	 */
	public function getAdminIps() {
		return $this->adminIps;
	}

	/**
	 * @param array $ips
     * @return static
	 */
	public function setAdminIps(array $ips) {
		$this->adminIps = $ips;
        return $this;
	}

	public function addAdminIp($ip) {
		$this->adminIps[] = $ip;
        return $this;
	}

	public function hasAdminIp($ip = null) {
		$ip = ($ip === null) ? request()->getIp() : $ip;

        if(is_array($this->adminIps)) {
			return (in_array($ip, $this->adminIps));
		}

		return false;
	}

}