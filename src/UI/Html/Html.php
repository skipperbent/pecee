<?php

namespace Pecee\UI\Html;

class Html
{
    public const CLOSE_TYPE_TAG = 'tag';
    public const CLOSE_TYPE_NONE = 'none';

    protected string $tag;
    protected array $innerHtml = [];
    protected string $closingType = self::CLOSE_TYPE_TAG;
    protected array $attributes = [];

    public function __construct(string $tag)
    {
        $this->tag = $tag;
    }

    /**
     * Append element
     * @param Html|string $element
     * @return static $this
     */
    public function append($element): self
    {
        $this->innerHtml[] = $element;

        return $this;
    }

    /**
     * Append to element
     * @param Html $element
     * @return static $this
     */
    public function appendTo(self $element): self
    {
        $element->append($this);
        return $this;
    }

    /**
     * Prepend element
     * @param Html|string $element
     * @return static $this
     */
    public function prepend($element): self
    {
        array_unshift($this->innerHtml, $element);

        return $this;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->innerHtml;
    }

    /**
     * @param array $html
     * @return static
     */
    public function setInnerHtml(array $html): self
    {
        $this->innerHtml = $html;

        return $this;
    }

    /**
     * @param string $html
     * @return static
     */
    public function addInnerHtml($html): self
    {
        $this->innerHtml[] = $html;

        return $this;
    }

    public function text(string $text): self
    {
        return $this->addInnerHtml($text);
    }

    /**
     * Replace attribute
     *
     * @param string $name
     * @param string|null $value
     * @return static
     */
    public function replaceAttribute(string $name, ?string $value = null): self
    {
        $this->attributes[$name] = [$value];

        return $this;
    }

    /**
     * Adds new attribute to the element.
     *
     * @param string $name
     * @param string|null $value
     * @return static
     */
    public function addAttribute(string $name, ?string $value = null): self
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
     * @return static
     */
    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $this->addAttribute($name, $value);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string|null $value
     * @param bool $replace
     * @return static
     */
    public function attr(string $name, ?string $value = null, bool $replace = true): self
    {
        if ($replace === true) {
            return $this->replaceAttribute($name, $value);
        }

        return $this->addAttribute($name, $value);
    }

    /**
     * @param string $id
     * @return static
     */
    public function id(string $id): self
    {
        return $this->addAttribute('id', $id);
    }

    /**
     * @param string $css
     * @return static
     */
    public function style(string $css): self
    {
        return $this->addAttribute('style', $css);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $output = '<' . $this->tag;

        foreach ($this->attributes as $key => $val) {
            $output .= ' ' . $key;
            if ($val[0] !== null || strtolower($key) === 'value') {
                $val = addslashes(join(' ', $val));
                $output .= '="' . $val . '"';
            }
        }

        $output .= '>';

        for ($i = 0, $max = count($this->innerHtml); $i < $max; $i++) {
            $html = $this->innerHtml[$i];
            $output .= ($html instanceof static) ? $html->render() : $html;
        }

        if ($this->closingType === static::CLOSE_TYPE_TAG) {
            $output .= '</' . $this->tag . '>';
        }

        return $output;
    }

    /**
     * Add class
     * @param string $class
     * @return static
     */
    public function addClass(string $class): self
    {
        return $this->addAttribute('class', $class);
    }

    /**
     * @return string $closingType
     */
    public function getClosingType(): string
    {
        return $this->closingType;
    }

    /**
     * @param string $closingType
     * @return static
     */
    public function setClosingType(string $closingType): self
    {
        $this->closingType = $closingType;

        return $this;
    }

    public function __toString()
    {
        return $this->render();
    }

    /**
     * @return array
     */
    public function getInnerHtml(): array
    {
        return $this->innerHtml;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getAttribute(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    public function getFirstAttribute(string $name, ?string $defaultValue = null): ?string
    {
        return $this->getAttribute($name)[0] ?? $defaultValue;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $tag
     * @return static $this
     */
    public function setTag(string $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return string
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * @param string $name
     * @return static
     */
    public function removeAttribute(string $name): self
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }

        return $this;
    }

}