<?php

namespace Pecee\UI\Form\Validation;

class ValidateNotNullOrEmpty extends ValidateInput
{
    protected $allowEmpty = false;

    public function validates(): bool
    {
        return (is_string($this->input->getValue()) && trim($this->input->getValue()) !== '' || is_array(input($this->input->getIndex())) && count(input($this->input->getIndex())) > 0);
    }

    public function getError(): string
    {
        return lang('%s is required', $this->input->getName());
    }

}