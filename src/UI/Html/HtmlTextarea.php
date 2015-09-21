<?php
namespace Pecee\UI\Html;
class HtmlTextarea extends \Pecee\UI\Html\Html {
	protected $value;
	public function __construct($name, $rows, $cols, $value=null) {
		parent::__construct('textarea');
		$this->value=\Pecee\String::HtmlEntities($value);
		//$this->closingTag = false;
		$this->addAttribute('name', $name);
		$this->addAttribute('rows', $rows);
		$this->addAttribute('cols', $cols);
		$this->setInnerHtml($this->value);
	}
	
	public function getValue() {
		return $this->value;
	}
}