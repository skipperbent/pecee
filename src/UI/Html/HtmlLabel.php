<?php
namespace Pecee\UI\Html;
class HtmlLabel extends \Pecee\UI\Html\Html {
	public function __construct($name, $for) {
		parent::__construct('label');
		if($for) {
			$this->addAttribute('for', $for);
		}
		//$this->closingTag = false;
		$this->setInnerHtml($name);
	}
}