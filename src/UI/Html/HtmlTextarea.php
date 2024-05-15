<?php

namespace Pecee\UI\Html;

class HtmlTextarea extends Html
{
    protected string $value = '';

    public function __construct(string $name, ?int $rows = null, ?int $cols = null, ?string $value = null)
    {
        parent::__construct('textarea');

        $this->closingType = static::CLOSE_TYPE_TAG;

        $this->addAttribute('name', $name);

        if ($rows !== null) {
            $this->rows($rows);
        }

        if ($cols !== null) {
            $this->cols($cols);
        }

        if ($value !== null) {
            $this->value = htmlentities((string)$value, ENT_QUOTES, app()->getCharset());
            $this->addInnerHtml($this->value);
        }
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return html_entity_decode($this->value, ENT_QUOTES, app()->getCharset());
    }

    /**
     * @param string $text
     * @return static
     */
    public function placeholder(string $text): self
    {
        $this->addAttribute('placeholder', $text);

        return $this;
    }

    /**
     * @param string $wrap
     * @return static
     */
    public function wrap(string $wrap): self
    {
        return $this->addAttribute('wrap', $wrap);
    }

    /**
     * @param int $rows
     * @return static
     */
    public function rows(int $rows): self
    {
        return $this->addAttribute('rows', $rows);
    }

    /**
     * @param int $cols
     * @return static
     */
    public function cols(int $cols): self
    {
        return $this->addAttribute('cols', $cols);
    }

}