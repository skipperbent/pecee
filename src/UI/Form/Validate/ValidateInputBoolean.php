<?php
namespace Pecee\UI\Form\Validate;
class ValidateInputBoolean extends ValidateInput {
	public function validate() {
		return ($this->value===true);
	}
	public function getErrorMessage() {
		// No error message for a bolean.
	}
}