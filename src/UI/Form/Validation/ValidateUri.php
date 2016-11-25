<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Url;

class ValidateUri extends ValidateInput
{

    protected $error;

    public function validates()
    {
        if ($this->input->getValue() && !Url::isValid($this->input->getValue())) {
            $this->error = lang('%s is not a valid link', $this->input->getName());

            return false;
        }

        return true;
    }

    public function getError()
    {
        return $this->error;
    }

}