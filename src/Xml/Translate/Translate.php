<?php
namespace Pecee\Xml\Translate;

use Pecee\Locale;

class Translate {
	protected static $instance;

	/**
	 * Get instance
	 * @return \Pecee\Xml\Translate\Translate
	 */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	protected $xml;
	protected $dir;

	public function __construct() {
		$this->dir = '../lang';
		$this->setLanguageXml();
	}

	public function lookup($key) {
		if(!$this->dir) {
			throw new TranslateException('XML language directory must be specified.');
		}

		$xml=new \SimpleXmlElement($this->xml);
		$node=null;

		if(strpos($key, '.') > -1) {
			$children = explode('.', $key);
			foreach($children as $i=>$child) {
				if($i === 0) {
					$node = (isset($xml->$child) ? $xml->$child : null);
				} else {
					$node = (isset($node->$child) ? $node->$child : null);
				}
			}
		} else {
			$node=isset($xml->$key) ? $xml->$key : null;
		}

		if(!is_null($node)) {
			return $node;
		}

		throw new TranslateException(sprintf('Key "%s" does not exist for locale "%s"', $key, Locale::getInstance()->getLocale()));
	}

	public function setLanguageXml() {
		$path = sprintf('%s/%s.xml', $this->dir, str_replace('-', '_', strtolower(Locale::getInstance()->getLocale())));
		if(!file_exists($path)) {
			throw new TranslateException(sprintf('Language file %s not found for locale %s', $path, Locale::getInstance()->getLocale()));
		}
		$this->xml = file_get_contents($path, FILE_USE_INCLUDE_PATH);
	}

	public function setDirectory($dir) {
		$this->dir = $dir;
	}

	public function getDir() {
		return $this->dir;
	}
}