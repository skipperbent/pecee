<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Str;

class ValidateEmail extends ValidateInput
{

    public function validates(): bool
    {
        return Str::isEmail($this->input->getValue());
    }

    public function getError(): string
    {
        return lang('%s is not a valid email', $this->input->getName());
    }

}