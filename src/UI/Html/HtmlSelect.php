<?php
namespace Pecee\UI\Html;

class HtmlSelect extends Html
{
    protected $options = [];
    protected $groups = [];
    protected $groupsDisabled;

    public function __construct($name)
    {
        parent::__construct('select');

        if ($name !== null) {
            $this->addAttribute('name', $name);
        }
    }

    public function default(string $text): self
    {
        array_unshift($this->options, new HtmlSelectOption($text));
        return $this;
    }

    public function add(?string $text = null, ?string $value = null, bool $selected = false): self
    {
        return $this->addOption(new HtmlSelectOption($text, $value, $selected));
    }

    /**
     * Add option
     * @param HtmlSelectOption $option
     * @return static
     */
    public function addOption(HtmlSelectOption $option): self
    {
        $group = $option->getGroup();

        if ($group !== null) {
            if (isset($this->groups[$group])) {
                $this->groups[$group][] = $option;
            } else {
                $this->groups[$group] = [$option];
            }
        }

        $this->options[] = $option;

        return $this;
    }

    /**
     * @return array|HtmlSelectOption[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return static
     */
    public function multiple()
    {
        return $this->addAttribute('multiple');
    }

    /**
     * @return static
     */
    public function required()
    {
        return $this->addAttribute('required');
    }

    /**
     * @return string
     */
    public function render()
    {
        /* @var $options array */
        foreach ($this->groups as $name => $options) {

            $html = new Html('optgroup');
            $html->addAttribute('label', $name);

            if ($this->groupsDisabled !== null && in_array(strtolower($name), $this->groupsDisabled) === true) {
                $html->addAttribute('disabled');
            }

            /* @var $option HtmlSelectOption */
            foreach ($options as $option) {
                $html->addInnerHtml($option);
            }

        }

        /* @var $option HtmlSelectOption */
        foreach ($this->options as $option) {
            if ($option->getGroup() === null) {
                $this->addInnerHtml($option);
            }
        }

        return parent::render();
    }

    /**
     * @return static
     */
    public function disabled()
    {
        return $this->addAttribute('disabled');
    }

    /**
     * Disable entire group
     * @param string $group
     * @return static
     */
    public function disableGroup($group)
    {
        $this->groupsDisabled[] = $group;

        return $this;
    }

}