<?php
namespace Pecee\UI\Html;
class HtmlScript extends Html {

	public function __construct($src, $type='text/javascript') {
		parent::__construct('script');
		$this->addAttribute('type', $type);
		$this->addAttribute('src', $src);
	}

}