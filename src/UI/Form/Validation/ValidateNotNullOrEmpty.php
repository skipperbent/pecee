<?php
namespace Pecee\UI\Form\Validation;

class ValidateNotNullOrEmpty extends ValidateInput
{
    protected $allowEmpty = false;

    public function validates()
    {
        return (is_string($this->input->getValue()) && trim($this->input->getValue()) !== '');
    }

    public function getError()
    {
        return lang('%s is required', $this->input->getName());
    }

}