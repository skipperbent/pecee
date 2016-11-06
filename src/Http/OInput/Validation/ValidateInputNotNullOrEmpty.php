<?php
namespace Pecee\Http\OInput\Validation;

class ValidateInputNotNullOrEmpty extends ValidateInput {

	public function validate() {
		return (!empty($this->value));
	}

	public function getErrorMessage() {
		return lang('%s is required', $this->name);
	}

}