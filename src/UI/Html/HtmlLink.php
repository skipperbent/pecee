<?php
namespace Pecee\UI\Html;

class HtmlLink extends Html {

	public function __construct($href, $rel = 'stylesheet', $type = null) {
		parent::__construct('link');

		$this->closingType = self::CLOSE_TYPE_SELF;
		$this->addAttribute('href', $href);
		$this->addAttribute('rel', $rel);

		if($type !== null) {
			$this->addAttribute('type', $type);
		}
	}

}