<?php

namespace Pecee\UI\Form\Validation;

class ValidateRegex extends ValidateInput
{
    protected string $regex;
    protected string $error = '';

    public function __construct(string $regex, string $error)
    {
        $this->regex = $regex;
        $this->error = $error;
    }

    public function validates(): bool
    {
        return (preg_match($this->regex, $this->input->getValue()) === 1);
    }

    public function getError(): string
    {
        return $this->error;
    }

}