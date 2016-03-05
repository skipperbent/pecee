<?php
namespace Pecee\UI\Html;
class HtmlMeta extends Html {

	public function __construct($content = null) {
		parent::__construct('meta');

		if($content !== null) {
			$this->addAttribute('content', $content);
		}

		$this->closingType = self::CLOSE_TYPE_SELF;
	}

}