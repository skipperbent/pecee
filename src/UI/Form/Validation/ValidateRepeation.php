<?php

namespace Pecee\UI\Form\Validation;

class ValidateRepeation extends ValidateInput
{
    protected $compareInput;
    protected $caseSensitive;

    public function __construct($compareIndex, $caseSensitive = true)
    {
        $this->compareInput = input()->find($compareIndex);
        $this->caseSensitive = $caseSensitive;
    }

    public function validates()
    {
        if ($this->caseSensitive === false) {
            return (strtolower($this->compareInput->getValue()) === strtolower($this->input->getValue()));
        }

        return ($this->compareInput->getValue() === $this->input->getValue());
    }

    public function getError()
    {
        return lang('%s is not equal to %s', $this->input->getName(), $this->compareInput->getName());
    }

}