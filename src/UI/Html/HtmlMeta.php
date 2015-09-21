<?php
namespace Pecee\UI\Html;
class HtmlMeta extends \Pecee\UI\Html\Html {
	public function __construct($content) {
		parent::__construct('meta');
		$this->addAttribute('content', $content);
		$this->closingType = self::CLOSE_TYPE_SELF;
	}
}