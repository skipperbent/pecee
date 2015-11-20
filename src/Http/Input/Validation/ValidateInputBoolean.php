<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputBoolean extends ValidateInput {
	public function validate() {
		return ($this->value===true);
	}

	public function getErrorMessage() {
		return lang('%s must be true or false', $this->name);
	}

}