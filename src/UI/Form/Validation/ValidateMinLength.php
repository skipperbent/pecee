<?php
namespace Pecee\UI\Form\Validation;

class ValidateMinLength extends ValidateInput
{

	protected $minimumLength;

	public function __construct($minimumLength = 5)
	{
		$this->minimumLength = $minimumLength;
	}

	public function validates()
	{
		if ($this->input->getValue()) {
			return ((strlen($this->input->getValue()) > $this->minimumLength));
		}

		return true;
	}

	public function getError()
	{
		return lang('%s has to minimum %s characters long', $this->input->getName(), $this->minimumLength);
	}

}