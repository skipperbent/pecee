<?php
namespace Pecee\UI\Html;

class HtmlInput extends Html {

	public function __construct($name, $type, $value = null) {
		parent::__construct('input', $value);
		$this->addAttribute('type', $type);
		$this->addAttribute('name', $name);
		$this->closingType = self::CLOSE_TYPE_SELF;

		if($value !== null){
			$this->addAttribute('value', $value);
		}
	}

    public function placeholder($text) {
        $this->addAttribute('placeholder', $text);
        return $this;
    }

    public function id($id) {
        $this->addAttribute('id', $id);
        return $this;
    }

    public function style($css) {
        $this->addAttribute('style', $css);
        return $this;
    }

}