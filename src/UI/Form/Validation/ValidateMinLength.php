<?php
namespace Pecee\UI\Form\Validation;

class ValidateMinLength extends ValidateInput
{
    protected $minimumLength;

    public function __construct($minimumLength = 5)
    {
        $this->minimumLength = $minimumLength;
    }

    public function validates(): bool
    {
        return (strlen($this->input->getValue()) >= $this->minimumLength);
    }

    public function getError(): string
    {
        return lang('%s has to be minimum %s characters long', $this->input->getName(), $this->minimumLength);
    }

}