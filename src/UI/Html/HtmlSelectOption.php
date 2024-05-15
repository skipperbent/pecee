<?php

namespace Pecee\UI\Html;

class HtmlSelectOption extends Html
{
    protected ?string $group = null;

    public function __construct(?string $text = null, ?string $value = null, bool $selected = false)
    {
        parent::__construct('option');

        $this->addAttribute('value', $value);

        if ($selected === true) {
            $this->selected();
        }

        if ($text !== null) {
            $this->addInnerHtml($text);
        }
    }

    public function selected(bool $selected = true): self
    {
        if ($selected === true) {
            $this->attr('selected');
        } else {
            $this->removeAttribute('selected');
        }

        return $this;
    }

    /**
     * Set group name
     * @param string $group
     * @return static
     */
    public function setGroup(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Set group name
     * @param string $group
     * @return static
     */
    public function group(string $group): self
    {
        return $this->setGroup($group);
    }

    /**
     * Get group name
     * @return string|null
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @return static
     */
    public function disabled(): self
    {
        return $this->addAttribute('disabled', null);
    }

}