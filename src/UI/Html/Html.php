<?php
namespace Pecee\UI\Html;

use Pecee\UI\Menu\Menu;
use Pecee\Widget\Widget;

class Html {

    protected $type;
    protected $innerHtml;
    protected $closingType;
    protected $attributes;

    const CLOSE_TYPE_SELF = 'self';
    const CLOSE_TYPE_TAG = 'tag';
    const CLOSE_TYPE_NONE = 'none';

    public function __construct($type) {
        $this->type = $type;
        $this->attributes = array();
        $this->closingType = self::CLOSE_TYPE_TAG;
        $this->innerHtml = array();
    }

    /**
     * @param array $html
     * @return self
     */
    public function setInnerHtml(array $html) {
        $this->innerHtml = $html;
        return $this;
    }


    public function addInnerHtml($html) {
        $this->innerHtml[] = $html;
        return $this;
    }

    public function addWidget(Widget $widget) {
        return $this->addInnerHtml($widget->__toString());
    }

    public function addMenu(Menu $menu) {
        return $this->addInnerHtml($menu->__toString());
    }

    public function addItem(Html $htmlItem) {
        return $this->addInnerHtml($htmlItem->__toString());
    }

    /**
     * Replace attribute
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function replaceAttribute($name, $value = '') {
        $this->attributes[$name] = array($value);
        return $this;
    }

    /**
     * Adds new attribute to the element.
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function addAttribute($name, $value = '') {
        if(!isset($this->attributes[$name])) {
            $this->attributes[$name] = array($value);
        } else {
            foreach($this->attributes[$name] as $val) {
                if($val === $value) {
                    return $this;
                }
            }
            $this->attributes[$name][] = $value;
        }

        return $this;
    }

    public function attr($name, $value = '', $replace = false) {
        if($replace) {
            return $this->replaceAttribute($name, $value);
        }
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
        $output = '<'.$this->type;
        foreach($this->attributes as $key =>  $val) {
            $output .= ' '.$key. (($val !== null || strtolower($key) === 'value') ? '="' . join(' ', $val) . '"' : '');
        }
        $output .= ($this->closingType === self::CLOSE_TYPE_SELF) ? '/>' : '>';
        foreach($this->innerHtml as $html) {
            $output.=$html;
        }
        $output .= (($this->closingType === self::CLOSE_TYPE_TAG) ? sprintf('</%s>',$this->type) : '');
        return $output;
    }

    /**
     * Add class
     * @param string $class
     * @return static
     */
    public function addClass($class) {
        $this->addAttribute('class', $class);
        return $this;
    }

    /**
     * @return string $closingType
     */
    public function getClosingType() {
        return $this->closingType;
    }

    public function getType() {
        return $this->type;
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

    public function getInnerHtml() {
        return $this->innerHtml;
    }

    public function getAttribute($name) {
        return (isset($this->attributes[$name]) ? $this->attributes[$name] : null);
    }

    public function getAttributes() {
        return $this->attributes;
    }

}