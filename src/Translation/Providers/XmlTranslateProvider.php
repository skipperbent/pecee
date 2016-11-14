<?php
namespace Pecee\Translation\Providers;

use Pecee\Exceptions\TranslationException;

class XmlTranslateProvider implements ITranslationProvider {

	protected $xml;
	protected $dir;

	public function __construct() {
		$this->dir = '../lang';
		$this->setLanguageXml();
	}

	public function lookup($key) {
		if(!$this->dir) {
			throw new TranslationException('XML language directory must be specified.');
		}

		$xml = new \SimpleXmlElement($this->xml);
		$node = null;

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
			$node = isset($xml->$key) ? $xml->$key : null;
		}

		if($node !== null) {
			return $node;
		}

		throw new TranslationException(sprintf('Key "%s" does not exist for locale "%s"', $key, request()->locale->getLocale()));
	}

	public function setLanguageXml() {
		$path = sprintf('%s/%s.xml', $this->dir, str_replace('-', '_', strtolower(request()->locale->getLocale())));

		if(!file_exists($path)) {
			throw new TranslationException(sprintf('Language file %s not found for locale %s', $path, request()->locale->getLocale()));
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