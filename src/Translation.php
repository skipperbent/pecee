<?php
namespace Pecee;

use Pecee\Model\ModelLanguage;
use Pecee\Xml\Translate\Translate;

class Translation {
	const TYPE_DATABASE='LANG_DB';
	const TYPE_XML='LANG_XML';

	protected static $instance;
	public static $TYPES = array(self::TYPE_DATABASE, self::TYPE_XML);

	protected $type;

	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->type = self::TYPE_DATABASE;
	}

	/**
	 * Translate message.
	 * @param string $key
	 * @param array $args
	 * @return string
	 */
	public function _($key, $args = null) {
		if (!is_array($args)) {
			$args = func_get_args();
			$args = array_slice($args, 1);
		}
		return vsprintf($this->lookup($key), $args);
	}

	protected function lookup($key) {
		switch($this->type) {
			case self::TYPE_DATABASE:
				return ModelLanguage::getInstance()->lookup($key);
			case self::TYPE_XML:
				return Translate::getInstance()->lookup($key);
		}
        return $key;
	}

	public function setType($languageType) {
		if(!in_array($languageType,self::$TYPES)) {
			throw new \InvalidArgumentException('Invalid language type defined');
		}
		$this->type=$languageType;
	}

	public function getType() {
		return $this->type;
	}
}