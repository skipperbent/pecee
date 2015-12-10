<?php
namespace Pecee\UI\Html;
class HtmlSelectOption extends Html {

	public function __construct($name, $value, $selected=false) {
		parent::__construct('option', $value);
		$this->addAttribute('value',$value);
		if($selected) {
			$this->addAttribute('selected', 'selected');
		}
		$this->setInnerHtml($name);
	}

}