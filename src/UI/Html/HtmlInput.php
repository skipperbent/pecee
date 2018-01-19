<?php

namespace Pecee\UI\Html;

class HtmlInput extends Html
{

    public function __construct($name, $type, $value = null)
    {
        parent::__construct('input');

        $this->type($type);
        $this->name($name);
        $this->setClosingType(static::CLOSE_TYPE_NONE);

        if ($value !== null) {
            $this->value($value);
        }

    }

    /**
     * @param string $name
     * @return static
     */
    public function name($name)
    {
        return $this->addAttribute('name', $name);
    }

    /**
     * @param string $value
     * @return static
     */
    public function value($value)
    {
        return $this->addAttribute('value', $value);
    }

    /**
     * @param string $text
     * @return static
     */
    public function placeholder($text)
    {
        return $this->addAttribute('placeholder', $text);
    }

    /**
     * @param bool $status
     * @return static
     */
    public function autoComplete($status = true)
    {
        return $this->addAttribute('autocomplete', (($status === true) ? 'on' : 'off'));
    }

    /**
     * @return static
     */
    public function readonly()
    {
        return $this->addInputAttribute('readonly');
    }

    /**
     * @return static
     */
    public function disabled()
    {
        return $this->addInputAttribute('disabled');
    }

    /**
     * @return static
     */
    public function autofocus()
    {
        return $this->addInputAttribute('autofocus');
    }

    /**
     * @return static
     */
    public function required()
    {
        return $this->addInputAttribute('required');
    }

    /**
     * @return static
     */
    public function multiple()
    {
        return $this->addInputAttribute('required');
    }

    /**
     * @param int $maxLength
     * @return static
     */
    public function maxLength($maxLength)
    {
        return $this->addAttribute('maxlength', $maxLength);
    }

    /**
     * @param int $size
     * @return static
     */
    public function size($size)
    {
        return $this->addAttribute('size', $size);
    }

    /**
     * @param int $type
     * @return static
     */
    public function type($type)
    {
        return $this->addAttribute('type', $type);
    }

    /**
     * @param int $pattern
     * @return static
     */
    public function pattern($pattern)
    {
        return $this->addAttribute('pattern', $pattern);
    }

    /**
     * @param static $checked
     * @return static
     */
    public function checked($checked)
    {
        if ($checked === true) {
            return $this->addInputAttribute('checked');
        }

        return $this->removeAttribute('checked');
    }

    /**
     * @param string $name
     * @return static $this
     */
    public function addInputAttribute($name)
    {
        $this->addAttribute($name, null);

        return $this;
    }

}