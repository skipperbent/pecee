<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Integer;

class ValidateInteger extends ValidateInput
{
    public function validates(bool $strict = true): bool
    {
        if($strict === false) {
            return Integer::isNummeric($this->input->getValue());
        }
        
        return Integer::isInteger($this->input->getValue());
    }

    public function getError(): string
    {
        return lang('%s is not a valid number', $this->input->getName());
    }

}