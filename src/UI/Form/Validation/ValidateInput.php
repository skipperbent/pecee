<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Http\Input\InputItem;

abstract class ValidateInput
{

	/**
	 * @var InputItem
	 */
	protected $input;
	protected $placement;

	abstract public function validates();

	abstract public function getError();

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
	 * @param InputItem $input
	 */
	public function setInput(InputItem $input)
	{
		$this->input = $input;
	}

	/**
	 * @return InputItem
	 */
	public function getInput()
	{
		return $this->input;
	}

}