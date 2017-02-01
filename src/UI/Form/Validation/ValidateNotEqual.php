<?php
namespace Pecee\UI\Form\Validation;

class ValidateNotEqual extends ValidateInput
{
    protected $value;
    protected $strict;
    protected $error;

    public function __construct($value, $strict = false)
    {
        $this->value = $value;
        $this->strict = $strict;
    }

    public function validates()
    {
        $value = $this->input->getValue();
        if ($this->strict === false) {
            $value = strtolower($value);
            $this->value = strtolower($this->value);
        }

        return $value !== $this->value;
    }

    public function getError()
    {
        return lang('%s is required', $this->input->getName());
    }

}