<?php
namespace Pecee\UI\Html;

use Pecee\UI\Menu\Menu;
use Pecee\Widget\Widget;

class Html {
	protected $name;
	protected $value;
	protected $innerHtml;
	protected $closingType;
	protected $attributes;

	const CLOSE_TYPE_SELF='self';
	const CLOSE_TYPE_TAG='tag';
	const CLOSE_TYPE_NONE='none';

	public function __construct($name, $value = null) {
		$this->name = $name;
		$this->value = $value;
		$this->attributes=array();
		$this->closingType=self::CLOSE_TYPE_TAG;
		$this->innerHtml=array();
	}

	/**
	 * @param string $innerHtml
	 */
	public function setInnerHtml($innerHtml) {
		$this->innerHtml[] = $innerHtml;
	}

	public function addWidget(Widget $widget) {
		$this->setInnerHtml($widget->__toString());
	}

	public function addMenu(Menu $menu) {
		$this->setInnerHtml($menu->__toString());
	}

	public function addItem(Html $htmlItem) {
		$this->setInnerHtml($htmlItem->__toString());
	}

	public function setElement(Html $el) {
		$this->innerHtml[]=$el->writeHtml();
	}

	/**
	 * Adds new attribute to the element.
	 *
	 * @param string $name
	 * @param string $value
	 * @return static
	 */
	public function addAttribute($name, $value='') {
		$this->attributes[$name] = $value;
		return $this;
	}

	public function attr($name, $value = '') {
		return $this->addAttribute($name, $value);
	}

	public function id($id) {
		$this->attr('id', $id);
		return $this;
	}

	public function style($css) {
		$this->attr('style', $css);
		return $this;
	}

	protected function writeHtml() {
		$output = '<'.$this->name;
		foreach($this->attributes as $key=>$val) {
			$output .= ' '.$key. ((!is_null($val) || strtolower($key) == 'value') ? '="'.$val.'"' : '');
		}
		$output .= ($this->closingType==self::CLOSE_TYPE_SELF) ? '/>' : '>';
		if($this->innerHtml) {
			foreach($this->innerHtml as $html) {
				$output.=$html;
			}
		}
		$output .= (($this->closingType == self::CLOSE_TYPE_TAG) ? sprintf('</%s>',$this->name) : '');
		return $output;
	}

	/**
	 * Add class
	 * @param string $class
     * @return static
	 */
	public function addClass($class) {
		$this->addAttribute('class',$class);
		return $this;
	}

	/**
	 * @return string $closingType
	 */
	public function getClosingType() {
		return $this->closingType;
	}

	public function getName() {
		return $this->name;
	}

	public function getValue() {
		return $this->value;
	}

	/**
	 * @param string $closingType
	 */
	public function setClosingType($closingType) {
		$this->closingType = $closingType;
	}

	public function __toString() {
		return $this->writeHtml();
	}
}