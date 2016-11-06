<?php
namespace Pecee\Http\OInput\Validation;

class ValidateInputBoolean extends ValidateInput {
	public function validate() {
		if($this->value) {
			return ($this->value === true || $this->value === false);
		}
		return true;
	}

	public function getErrorMessage() {
		return lang('%s must be true or false', $this->name);
	}

}