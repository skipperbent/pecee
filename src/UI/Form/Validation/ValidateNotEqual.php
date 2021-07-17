<?php
namespace Pecee\UI\Form\Validation;

class ValidateNotEqual extends ValidateInput
{
    protected string $value;
    protected bool $strict;
    protected string $error = '';

    public function __construct(string $value, bool $strict = false)
    {
        $this->value = $value;
        $this->strict = $strict;
    }

    public function validates(): bool
    {
        $value = $this->input->getValue();
        if ($this->strict === false) {
            $value = strtolower($value);
            $this->value = strtolower($this->value);
        }

        return $value !== $this->value;
    }

    public function getError(): string
    {
        return lang('%s is required', $this->input->getName());
    }

}