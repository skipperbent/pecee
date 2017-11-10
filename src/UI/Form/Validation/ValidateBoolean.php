<?php
namespace Pecee\UI\Form\Validation;

class ValidateBoolean extends ValidateInput
{

    public function validates()
    {
        return ($this->input->getValue() === true || $this->input->getValue() === false);
    }

    public function getError()
    {
        return lang('%s must be true or false', $this->input->getName());
    }

}