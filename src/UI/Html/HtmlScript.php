<?php
namespace Pecee\UI\Html;

class HtmlScript extends Html {

	public function __construct($src) {
		parent::__construct('script');

		$this->addAttribute('src', $src);
	}

}