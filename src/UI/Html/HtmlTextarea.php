<?php
namespace Pecee\UI\Html;

class HtmlTextarea extends Html
{
    protected $value;

    public function __construct($name, $rows = null, $cols = null, $value = null)
    {
        parent::__construct('textarea');

        $this->closingType = static::CLOSE_TYPE_TAG;

        $this->value = htmlentities($value, ENT_QUOTES, app()->getCharset());

        $this->addAttribute('name', $name);

        if ($rows !== null) {
            $this->rows($rows);
        }

        if ($cols !== null) {
            $this->cols($cols);
        }

        if ($this->value !== null) {
            $this->addInnerHtml($this->value);
        }
    }

    public function getValue()
    {
        return html_entity_decode($this->value, ENT_QUOTES, app()->getCharset());
    }

    public function placeholder($text)
    {
        $this->addAttribute('placeholder', $text);

        return $this;
    }

    public function wrap($wrap)
    {
        return $this->addAttribute('wrap', $wrap);
    }

    public function rows($rows)
    {
        return $this->addAttribute('rows', $rows);
    }

    public function cols($cols)
    {
        return $this->addAttribute('cols', $cols);
    }

}