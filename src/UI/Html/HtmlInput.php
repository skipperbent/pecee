<?php

namespace Pecee\UI\Html;

class HtmlInput extends Html
{

    public function __construct($name, $type, $value = null)
    {
        parent::__construct('input');

        $this->type($type);
        $this->name($name);

        if ($value !== null) {
            $this->value($value);
        }

    }

    public function name($name)
    {
        return $this->addAttribute('name', $name);
    }

    public function value($value)
    {
        return $this->addAttribute('value', $value);
    }

    public function placeholder($text)
    {
        return $this->addAttribute('placeholder', $text);
    }

    public function autoComplete($status = true)
    {
        return $this->addAttribute('autocomplete', (($status === true) ? 'on' : 'off'));
    }

    public function readonly()
    {
        return $this->addInputAttribute('readonly');
    }

    public function disabled()
    {
        return $this->addInputAttribute('disabled');
    }

    public function autofocus()
    {
        return $this->addInputAttribute('autofocus');
    }

    public function required()
    {
        return $this->addInputAttribute('required');
    }

    public function multiple()
    {
        return $this->addInputAttribute('required');
    }

    public function maxLength($maxLength)
    {
        return $this->addAttribute('maxlength', $maxLength);
    }

    public function size($size)
    {
        return $this->addAttribute('size', $size);
    }

    public function type($type)
    {
        return $this->addAttribute('type', $type);
    }

    public function pattern($pattern)
    {
        return $this->addAttribute('pattern', $pattern);
    }

    public function checked($checked)
    {
        if ($checked === true) {
            return $this->addInputAttribute('checked');
        }

        return $this->removeAttribute('checked');
    }

    public function addInputAttribute($name)
    {
        $this->addAttribute($name, null);

        return $this;
    }

}