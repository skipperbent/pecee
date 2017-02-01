<?php
namespace Pecee\UI\Form\Validation;

class ValidateMaxLength extends ValidateInput
{

    protected $maxLength;

    public function __construct($maxLength = 50)
    {
        $this->maxLength = $maxLength;
    }

    public function validates()
    {
        return !(strlen($this->input->getValue()) > $this->maxLength);
    }

    public function getError()
    {
        return lang('%s can only be %s characters', $this->input->getName(), $this->maxLength);
    }

}