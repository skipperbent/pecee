<?php
namespace Pecee\UI\Html;

class HtmlSelectOption extends Html {

	public function __construct($name, $value, $selected = false) {
		parent::__construct('option');

        $this->addAttribute('value', $value);

		if($selected === true) {
			$this->addAttribute('selected', 'selected');
		}
        
		$this->addInnerHtml($name);
	}

}