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
		if ($this->input->getValue()) {
			$value = $this->input->getValue();
			if (!$this->strict) {
				$value = strtolower($value);
				$this->value = strtolower($this->value);
			}

			if ($value === $this->value) {
				$this->error = lang('%s is required', $this->input->getName());

				return false;
			}
		}

		return true;
	}

	public function getError()
	{
		return $this->error;
	}

}