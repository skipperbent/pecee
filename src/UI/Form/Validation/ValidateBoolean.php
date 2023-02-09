<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Boolean;

class ValidateBoolean extends ValidateInput
{

    public function validates(): bool
    {
        return Boolean::parse($this->input->getValue());
    }

    public function getError(): string
    {
        return lang('%s must be true or false', $this->input->getName());
    }

}