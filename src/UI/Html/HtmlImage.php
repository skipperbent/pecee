<?php
namespace Pecee\UI\Html;
class HtmlImage extends \Pecee\UI\Html\Html {
	public function __construct($src, $alt = null) {
		parent::__construct('img');
		$this->closingType = self::CLOSE_TYPE_SELF;
		$this->addAttribute('src', $src);
		$this->addAttribute('alt', $alt);	
	}
}