<?php
namespace Pecee\UI\Html;

use Pecee\Str;

class HtmlTextarea extends Html {

	protected $value;
	public function __construct($name, $rows, $cols, $value=null) {
		parent::__construct('textarea');
		$this->value = Str::htmlEntities($value);
		//$this->closingTag = false;
		$this->addAttribute('name', $name);
		$this->addAttribute('rows', $rows);
		$this->addAttribute('cols', $cols);
		$this->setInnerHtml($this->value);
	}

	public function getValue() {
		return $this->value;
	}

	public function placeholder($text) {
		$this->addAttribute('placeholder', $text);
		return $this;
	}

}