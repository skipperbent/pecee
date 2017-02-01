<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Integer;

class ValidateInteger extends ValidateInput
{
    public function validates($strict = true)
    {
        if($strict === false) {
            return Integer::isNummeric($this->input->getValue());
        }
        
        return Integer::isInteger($this->input->getValue());
    }

    public function getError()
    {
        return lang('%s is not a valid number', $this->input->getName());
    }

}