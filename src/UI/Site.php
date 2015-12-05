<?php
namespace Pecee\UI;
use Pecee\Auth;
use Pecee\Debug;
use Pecee\Locale;
use Pecee\UI\Html\Html;
use Pecee\UI\Html\HtmlLink;
use Pecee\UI\Html\HtmlMeta;
use Pecee\UI\Html\HtmlScript;

class Site {

	const DOCTYPE_HTML_5 = '<!DOCTYPE html>';
	const DOCTYPE_XHTML_DEFAULT = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	const DOCTYPE_XHTML_STRICT = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

	const CHARSET_UTF8 = 'UTF-8';

	// Settings
	public static $docTypes = array(self::DOCTYPE_XHTML_DEFAULT, self::DOCTYPE_XHTML_STRICT, self::DOCTYPE_HTML_5);
	private static $instance;

	protected $docType;
	protected $charset;
	protected $title;
	protected $description;
	protected $keywords;
	protected $header = array();
	protected $js = array();
	protected $css = array();
	protected $jsPath;
	protected $cssPath;
	protected $jsFilesWrapped = array();
	protected $cssFilesWrapped = array();
	protected $debug;
	protected $locale;
    protected $cacheEnabled;
    protected $cacheDir;
	protected $adminIps;

	/**
	 * Get new instance
	 * @return \Pecee\UI\Site
	 */
	public static function getInstance() {
		if(is_null(self::$instance)) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function __construct() {
		// Load default settings
		$this->docType = self::DOCTYPE_HTML_5;
		$this->charset = self::CHARSET_UTF8;
		$this->jsPath = 'js/';
		$this->cssPath = 'css/';
		$this->keywords = array();
		$this->debug = (isset($_GET['__debug']) && strtolower($_GET['__debug']) == 'true' && $this->hasAdminIp());
		$this->locale = Locale::getInstance();
        $this->cacheDir = dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . 'cache';
        $this->cacheEnabled = true;
		$this->adminIps = array();
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getDescription() {
		return $this->description;
	}

	public function setDescription($description) {
		$this->description = $description;
	}

	public function setDocType($doctype) {
		if(!in_array($doctype, self::$docTypes))
			throw new \InvalidArgumentException('Unknown doctype.');
		$this->docType = $doctype;
	}
	public function getDocType() {
		return $this->docType;
	}

	public function getCharset() {
		return $this->charset;
	}
	public function addWrappedJs($filename) {
		if(!in_array($filename, $this->jsFilesWrapped)) {
			$this->jsFilesWrapped[] = $filename;
		}
	}
	public function removeWrappedJs($filename) {
		if(in_array($filename, $this->jsFilesWrapped)) {
			$key = array_search($filename, $this->jsFilesWrapped);
			unset($this->jsFilesWrapped[$key]);
		}
	}

	public function removeWrappedCss($filename) {
		if(in_array($filename, $this->cssFilesWrapped)) {
			$key = array_search($filename, $this->cssFilesWrapped);
			unset($this->cssFilesWrapped[$key]);
		}
	}

	public function addWrappedCss($filename) {
		if(!in_array($filename, $this->cssFilesWrapped)) {
			$this->cssFilesWrapped[] = $filename;
		}
	}

	public function setDebug($debug) {
		// Enable or disable debugging
		Debug::getInstance()->setEnabled($debug);

		$this->debug = $debug;
	}

	public function getDebug() {
		return $this->debug;
	}

    public function getCacheEnabled() {
        return $this->cacheEnabled;
    }

    public function setCacheEnabled($bool) {
        $this->cacheEnabled = $bool;
    }

    public function getCacheDir() {
        return $this->cacheDir;
    }

    public function setCacheDir($dir) {
        $this->cacheDir = $dir;
    }

	public function addCss($path) {
		$css = new HtmlLink($path, 'stylesheet', 'text/css');
		if(!in_array($css, $this->css)) {
			$this->css[] = $css;
		}
	}

	public function addJs($path) {
		$js = new HtmlScript($path);
		if(!in_array($js, $this->js)) {
			$this->js[] = $js;
		}
	}

	public function clearCss() {
		$this->cssFilesWrapped=array();
	}

	public function clearJs() {
		$this->jsFilesWrapped=array();
	}

	public function setCssPath($path) {
		$this->cssPath = $path;
	}

	public function setJsPath($path) {
		$this->jsPath = $path;
	}

	public function getCssPath() {
		return $this->cssPath;
	}

	public function getJsPath() {
		return $this->jsPath;
	}

	public function setKeywords(array $keywords) {
		$this->keywords = $keywords;
		return $this;
	}

	public function getKeywords() {
		return $this->keywords;
	}

	public function addMeta($name, $content) {
		$meta=new HtmlMeta($content);
		$meta->addAttribute('name', $name);
		return $this->addHeader($meta);
	}

	public function addHeader(Html $el) {
		$this->header[] = $el;
		return $this;
	}

	public function getJsFilesWrapped() {
		return $this->jsFilesWrapped;
	}

	public function getCssFilesWrapped() {
		return $this->cssFilesWrapped;
	}

	public function getJs() {
		return $this->js;
	}

	public function getCss() {
		return $this->css;
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
	 * @param array $adminIps
	 */
	public function setAdminIps(array $adminIps) {
		$this->adminIps = $adminIps;
	}


	public function addAdminIp($ip) {
		$this->adminIps[] = $ip;
	}

	public function hasAdminIp($ip = null) {
		$ip = ($ip === null) ? request()->getIp() : $ip;
		if(is_array($this->adminIps)) {
			return (in_array($ip, $this->adminIps));
		}
		return false;
	}

}