<?php
namespace Pecee\UI\Html;

class HtmlScript extends Html {

	public function __construct($src, $type = null) {
		parent::__construct('script');

		if($type !== null) {
			$this->addAttribute('type', $type);
		}

		$this->addAttribute('src', $src);
	}

}