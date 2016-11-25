<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Integer;

class ValidateMaxValue extends ValidateInput
{

    protected $maxValue;
    protected $error;

    public function __construct($maxValue)
    {
        $this->maxValue = $maxValue;
    }

    public function validates()
    {
        if ($this->input->getValue()) {
            if (!Integer::isInteger($this->input->getValue())) {
                $this->error = lang('%s is not a valid number', $this->input->getName());
            }
            if ($this->input->getValue() > $this->maxValue) {
                $this->error = lang('%s cannot be greater than %s', $this->input->getName(), $this->maxValue);
            }

            return !($this->error);
        }

        return true;
    }

    public function getError()
    {
        return $this->error;
    }

}