<?php

namespace Pecee\UI\Html;

class HtmlSelectOption extends Html
{
    protected $group;

    public function __construct($value, $text = null, $selected = false)
    {
        parent::__construct('option');

        $this->addAttribute('value', $value);

        if ($selected === true) {
            $this->selected('selected', null);
        }

        if ($text !== null) {
            $this->addInnerHtml($text);
        }
    }

    public function selected(bool $selected = true)
    {
        if ($selected === true) {
            $this->attr('selected');
        } else {
            $this->removeAttribute('selected');
        }
    }

    /**
     * Set group name
     * @param string $group
     * @return static
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Set group name
     * @param string $group
     * @return static
     */
    public function group($group)
    {
        return $this->setGroup($group);
    }

    /**
     * Get group name
     * @return string|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return static
     */
    public function disabled()
    {
        return $this->addAttribute('disabled', null);
    }

}