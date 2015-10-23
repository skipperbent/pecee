<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputBoolean extends ValidateInput {
	public function validate() {
		return ($this->value===true);
	}

	public function getErrorMessage() {
		// No error message for a bolean.
	}

}