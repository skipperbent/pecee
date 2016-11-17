<?php
namespace Pecee\UI\Html;

class HtmlImage extends Html {

	public function __construct($src, $alt = '') {

		parent::__construct('img');

		$this->closingType = self::CLOSE_TYPE_SELF;

		$this->addAttribute('src', $src);
		$this->addAttribute('alt', $alt);

	}

}