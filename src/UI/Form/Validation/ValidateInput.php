<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Http\Input\IInputItem;

abstract class ValidateInput {

    /**
     * @var IInputItem
     */
	protected $input;
	protected $placement;

	abstract public function validates();
	abstract public function getError();

    /**
     * @return string|null
     */
    public function getPlacement() {
        return $this->placement;
    }

    /**
     * @param string|null $placement
     */
    public function setPlacement($placement) {
        $this->placement = $placement;
    }

	/**
	 * @param IInputItem $input
	 */
	public function setInput(IInputItem $input) {
		$this->input = $input;
	}

	/**
	 * @return IInputItem
	 */
	public function getInput() {
		return $this->input;
	}

}