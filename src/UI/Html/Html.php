<?php
namespace Pecee\UI\Html;

class Html
{

    const CLOSE_TYPE_SELF = 'self';
    const CLOSE_TYPE_TAG = 'tag';
    const CLOSE_TYPE_NONE = 'none';

    protected $tag;
    protected $innerHtml = [];
    protected $closingType = self::CLOSE_TYPE_TAG;
    protected $attributes = [];

    public function __construct($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @param array $html
     * @return static
     */
    public function setInnerHtml(array $html)
    {
        $this->innerHtml = $html;

        return $this;
    }

    public function addInnerHtml($html)
    {
        $this->innerHtml[] = $html;

        return $this;
    }

    public function addItem($htmlItem)
    {
        if ($htmlItem instanceof static) {
            return $this->addInnerHtml($htmlItem->__toString());
        }

        return $this->addInnerHtml($htmlItem);
    }

    /**
     * Replace attribute
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function replaceAttribute($name, $value = '')
    {
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
    public function addAttribute($name, $value = '')
    {
        if (isset($this->attributes[$name]) && in_array($value, $this->attributes[$name], true) === false) {
            $this->attributes[$name][] = $value;
        } else {
            $this->attributes[$name] = [$value];
        }

        return $this;
    }

    /**
     * @param array $attributes
     * @return static $this
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->addAttribute($name, $value);
        }

        return $this;
    }

    public function attr($name, $value = '', $replace = true)
    {
        if ($replace === true) {
            return $this->replaceAttribute($name, $value);
        }

        return $this->addAttribute($name, $value);
    }

    public function id($id)
    {
        return $this->attr('id', $id);
    }

    public function style($css)
    {
        return $this->attr('style', $css);
    }

    protected function writeHtml()
    {
        $output = '<' . $this->tag;

        foreach ($this->attributes as $key => $val) {
            $output .= ' ' . $key;
            if ($val[0] !== null || strtolower($key) === 'value') {
                $val = htmlentities(join(' ', $val), ENT_QUOTES, app()->getCharset());
                $output .= '="' . $val . '"';
            }
        }

        return $output . '>' . join('', $this->innerHtml) . (($this->closingType === self::CLOSE_TYPE_TAG) ? '</' . $this->tag . '>' : '');
    }

    /**
     * Add class
     * @param string $class
     * @return static
     */
    public function addClass($class)
    {
        return $this->attr('class', $class, false);
    }

    /**
     * @return string $closingType
     */
    public function getClosingType()
    {
        return $this->closingType;
    }

    /**
     * @param string $closingType
     * @return static $this;
     */
    public function setClosingType($closingType)
    {
        $this->closingType = $closingType;

        return $this;
    }

    public function __toString()
    {
        return $this->writeHtml();
    }

    public function getInnerHtml()
    {
        return $this->innerHtml;
    }

    public function getAttribute($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function removeAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }

        return $this;
    }

}