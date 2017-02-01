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
    abstract public function validates();

    abstract public function getError();

    /**
     * Validate both custom validation and build-in validation (like empty values and framework specific stuff).
     */
    public function runValidation()
    {
        if($this->allowEmpty === true && empty($this->input->getValue()) === true) {
            return true;
        }

        return $this->validates();
    }

    /**
     * @return string|null
     */
    public function getPlacement()
    {
        return $this->placement;
    }

    /**
     * @param string|null $placement
     */
    public function setPlacement($placement)
    {
        $this->placement = $placement;
    }

    /**
     * @param IInputItem $input
     */
    public function setInput(IInputItem $input)
    {
        $this->input = $input;
    }

    /**
     * @return IInputItem
     */
    public function getInput()
    {
        return $this->input;
    }

    public function getAllowEmpty()
    {
        return $this->allowEmpty;
    }

}