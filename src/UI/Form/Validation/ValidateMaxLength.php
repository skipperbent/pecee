<?php
namespace Pecee\UI\Form\Validation;

class ValidateMaxLength extends ValidateInput
{

    protected int $maxLength;

    public function __construct(int $maxLength = 50)
    {
        $this->maxLength = $maxLength;
    }

    public function validates(): bool
    {
        return !(strlen($this->input->getValue()) > $this->maxLength);
    }

    public function getError(): string
    {
        return lang('%s can only be %s characters', $this->input->getName(), $this->maxLength);
    }

}