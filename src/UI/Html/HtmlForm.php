<?php
namespace Pecee\UI\Html;
class HtmlForm extends \Pecee\UI\Html\Html {
	public function __construct($name, $method, $action, $enctype) {
		parent::__construct('form');
		$this->closingType = self::CLOSE_TYPE_NONE;
		$this->outerTag=true;
		$this->addAttribute('name', $name);
		$this->addAttribute('enctype', $enctype);
		$this->addAttribute('method', $method);
		$this->addAttribute('action', ((!$action) ? url() : $action));
	}
}