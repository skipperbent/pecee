<?php
namespace Pecee\UI\Html;

class HtmlSelectOption extends Html
{

	public function __construct($value, $text = null, $selected = false)
	{
		parent::__construct('option');

		$this->addAttribute('value', $value);

		if ($selected === true) {
			$this->addAttribute('selected', null);
		}

		if ($text !== null) {
			$this->addInnerHtml($text);
		}
	}

}