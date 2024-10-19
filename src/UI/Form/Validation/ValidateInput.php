<?php

namespace Pecee\UI\Form\Validation;

use Pecee\Http\Input\IInputItem;

/**
 * Class ValidateInput
 * @package Pecee\UI\Form\Validation
 */
abstract class ValidateInput
{
    /**
     * When set to true empty values will not trigger validation-error unless defined in custom validation-method.
     * @var bool
     */
    protected $allowEmpty = true;

    /**
     * @var IInputItem
     */
    protected $input;
    protected $placement;

    /**
     * Custom validation method
     * @return bool
     */
    abstract public function validates(): bool;

    abstract public function getError(): string;

    /**
     * Validate both custom validation and build-in validation (like empty values and framework specific stuff).
     * @return bool
     */
    public function runValidation(): bool
    {
        if ($this->allowEmpty === true && trim((string)$this->input->getValue()) === '') {
            return true;
        }

        return $this->validates();
    }

    /**
     * @return string|null
     */
    public function getPlacement(): ?string
    {
        return $this->placement;
    }

    /**
     * @param string|null $placement
     * @return static
     */
    public function setPlacement(?string $placement): self
    {
        $this->placement = $placement;

        return $this;
    }

    /**
     * @param IInputItem $input
     * @return static;
     */
    public function setInput(IInputItem $input): self
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @return IInputItem
     */
    public function getInput(): IInputItem
    {
        return $this->input;
    }

    public function getAllowEmpty(): bool
    {
        return $this->allowEmpty;
    }

    public function setAllowEmpty(bool $allowEmpty): self
    {
        $this->allowEmpty = $allowEmpty;
        return $this;
    }

}