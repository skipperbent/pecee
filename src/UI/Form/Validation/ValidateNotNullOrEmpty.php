<?php
namespace Pecee\UI\Form\Validation;

class ValidateNotNullOrEmpty extends ValidateInput
{
    protected bool $allowEmpty = false;

    public function validates(): bool
    {
        return (is_string($this->input->getValue()) && trim($this->input->getValue()) !== '');
    }

    public function getError(): string
    {
        return lang('%s is required', $this->input->getName());
    }

}