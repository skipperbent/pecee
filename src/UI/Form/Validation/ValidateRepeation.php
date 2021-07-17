<?php

namespace Pecee\UI\Form\Validation;

use Pecee\Http\Input\IInputItem;

class ValidateRepeation extends ValidateInput
{
    protected ?IInputItem $compareInput = null;
    protected string $compareIndex;
    /**
     * @var mixed
     */
    protected $compareValue;
    protected bool $caseSensitive;

    public function __construct(string $compareIndex, bool $caseSensitive = true)
    {
        $this->compareIndex = $compareIndex;
        $this->compareInput = input()->find($compareIndex) ? input()->find($compareIndex)->getValue() : null;
        $this->caseSensitive = $caseSensitive;
    }

    public function validates(): bool
    {
        if ($this->caseSensitive === false) {
            return (strtolower($this->compareValue) === strtolower($this->input->getValue()));
        }

        return ($this->compareValue === $this->input->getValue());
    }

    public function getError(): string
    {
        return lang('%s is not equal to %s', $this->input->getName(), $this->compareIndex);
    }

}