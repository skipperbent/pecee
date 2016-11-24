<?php
namespace Pecee\UI\Html;

class HtmlSelect extends Html
{

	protected $options = [];

	public function __construct($name = null)
	{
		parent::__construct('select');

		if ($name !== null) {
			$this->addAttribute('name', $name);
		}
	}

	public function addOption(HtmlSelectOption $option)
	{
		$this->options[] = $option;
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function writeHtml()
	{
		/* @var $option \Pecee\UI\Html\HtmlSelectOption */
		foreach ($this->options as $option) {
			$this->addInnerHtml($option->writeHtml());
		}

		return parent::writeHtml();
	}

}