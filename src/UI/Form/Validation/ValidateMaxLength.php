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
        if ($this->input->getValue()) {
            return !(strlen($this->input->getValue()) > $this->maxLength);
        }

        return true;
    }

    public function getError()
    {
        return lang('%s can only be %s characters', $this->input->getName(), $this->maxLength);
    }

}