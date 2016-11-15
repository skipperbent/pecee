<?php
namespace Pecee\UI\Html;

class HtmlLabel extends Html {

	public function __construct($name, $for = null) {
		parent::__construct('label');

        if($for !== null) {
			$this->addAttribute('for', $for);
		}

		$this->addInnerHtml($name);
	}
    
}