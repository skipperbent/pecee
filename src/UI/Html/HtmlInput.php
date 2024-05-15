<?php

namespace Pecee\UI\Html;

class HtmlInput extends Html
{

    public function __construct(string $name, string $type, ?string $value = null)
    {
        parent::__construct('input');

        $this->type($type);
        $this->name($name);
        $this->setClosingType(static::CLOSE_TYPE_NONE);

        // Comply with html standard
        $this->autoComplete();

        if ($value !== null) {
            $this->value($value);
        }

    }

    /**
     * @param string $name
     * @return static
     */
    public function name(string $name): self
    {
        return $this->addAttribute('name', $name);
    }

    /**
     * @param string $value
     * @return static
     */
    public function value(string $value): self
    {
        return $this->addAttribute('value', $value);
    }

    /**
     * @param string $text
     * @return static
     */
    public function placeholder(string $text): self
    {
        return $this->addAttribute('placeholder', $text);
    }

    /**
     * @param bool $status
     * @return static
     */
    public function autoComplete(bool $status = false): self
    {
        return $this->addAttribute('autocomplete', (($status === true) ? 'on' : 'off'));
    }

    /**
     * @return static
     */
    public function readonly(): self
    {
        return $this->addAttribute('readonly');
    }

    /**
     * @return static
     */
    public function disabled(): self
    {
        return $this->addAttribute('disabled');
    }

    /**
     * @return static
     */
    public function autoFocus(): self
    {
        return $this->addAttribute('autofocus');
    }

    /**
     * @return static
     */
    public function required(): self
    {
        return $this->addAttribute('required');
    }

    /**
     * @return static
     */
    public function multiple(): self
    {
        return $this->addAttribute('required');
    }

    /**
     * @param int $maxLength
     * @return static
     */
    public function maxLength(int $maxLength): self
    {
        return $this->addAttribute('maxlength', $maxLength);
    }

    /**
     * @param int $size
     * @return static
     */
    public function size(int $size): self
    {
        return $this->addAttribute('size', $size);
    }

    /**
     * @param string $type
     * @return static
     */
    public function type(string $type): self
    {
        return $this->addAttribute('type', $type);
    }

    /**
     * @param int $pattern
     * @return static
     */
    public function pattern(int $pattern): self
    {
        return $this->addAttribute('pattern', $pattern);
    }

    /**
     * @param bool $checked
     * @return static
     */
    public function checked(bool $checked): self
    {
        if ($checked === true) {
            return $this->addAttribute('checked');
        }

        return $this->removeAttribute('checked');
    }

}