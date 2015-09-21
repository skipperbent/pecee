<?php
namespace Pecee\UI\Html;
class HtmlInput extends \Pecee\UI\Html\Html {
	public function __construct($name, $type, $value = null) {
		parent::__construct('input', $value);
		$this->addAttribute('type', $type);
		$this->addAttribute('name', $name);
		$this->closingType = self::CLOSE_TYPE_SELF;
		
		if(!is_null($value)){
			$this->addAttribute('value', $value);
		}
	}
	
	public function setChecked($bool) {
		if($bool)  {
			$this->attributes['checked'] = 'checked';
		} else {
			unset($this->attributes['checked']);
		}
		return $this;
	}
}