<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Str;

class ValidateEmail extends ValidateInput
{

	public function validates()
	{
		if ($this->input->getValue()) {
			return Str::isEmail($this->input->getValue());
		}

		return true;
	}

	public function getError()
	{
		return lang('%s is not a valid email', $this->input->getName());
	}

}