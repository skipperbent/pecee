<?php
namespace Pecee\UI\Form\Validation;

use Pecee\FloatUtil;

class ValidateFloat extends ValidateInput
{
    public function validates()
    {
        return FloatUtil::isFloat($this->input->getValue());
    }

    public function getError()
    {
        return lang('%s is not a valid number', $this->input->getValue());
    }

}