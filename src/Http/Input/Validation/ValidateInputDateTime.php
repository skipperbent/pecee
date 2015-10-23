<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputDateTime extends ValidateInput {

	public function validate() {
		return (\Pecee\Date::IsValid($this->value));
	}
	
	public function getErrorMessage() {
		return lang('%s is not a valid date time', $this->name);
	}

}