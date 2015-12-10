<?php
namespace Pecee\UI\Html;

class HtmlSelect extends Html {

	protected $options;
	public function __construct($name) {
		parent::__construct('select');
		$this->options=array();
		if(!is_null($name)) {
			$this->addAttribute('name', $name);
		}
	}

	public function addOption(HtmlSelectOption $option) {
		$this->options[]=$option;
	}

	public function getOptions() {
		return $this->options;
	}

	public function writeHtml() {
		/* @var $option \Pecee\UI\Html\HtmlSelectOption */
		foreach($this->options as $option) {
			$this->setInnerHtml($option->writeHtml());
		}
		return parent::writeHtml();
	}

}