<?php

namespace Pecee\UI\Form\Validation;

class ValidateRegex extends ValidateInput
{
    protected $regex;
    protected $errorMessage;

    public function __construct($regex, $errorMessage)
    {
        $this->regex = $regex;
        $this->errorMessage = $errorMessage;
    }

    public function validates()
    {
        return (preg_match($this->regex, $this->input->getValue()) !== 0);
    }

    public function getError()
    {
        return $this->errorMessage;
    }

}