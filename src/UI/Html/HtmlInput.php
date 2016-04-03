<?php
namespace Pecee\UI\Html;

use Pecee\Str;

class HtmlInput extends Html {

	public function __construct($name, $type, $value = null) {
		parent::__construct('input');
		$this->addAttribute('type', $type);
		$this->addAttribute('name', $name);
		$this->closingType = self::CLOSE_TYPE_SELF;

		if($value !== null){
			$this->addAttribute('value', Str::htmlEntities($value));
		}
	}

    public function placeholder($text) {
        $this->addAttribute('placeholder', $text);
        return $this;
    }

	public function autoComplete($bool = false) {
		$this->addAttribute('autocomplete', (($bool) ? 'on' : 'off'));
		return $this;
	}

}