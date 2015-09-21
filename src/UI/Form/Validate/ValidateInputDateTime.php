<?php
namespace Pecee\UI\Form\Validate;
class ValidateInputDateTime extends ValidateInput {	
	public function validate() {
		return (\Pecee\Date::IsValid($this->value));
	}
	
	public function getErrorMessage() {
		return lang('%s is not a valid date time', $this->name);
	}
}