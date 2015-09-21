<?php
namespace Pecee\UI\Form\Validate;
class ValidateInputNotNullOrEmpty extends ValidateInput {
	public function validate() {
		return (!empty($this->value));
	}
	public function getErrorMessage() {
		return lang('%s is required', $this->name);
	}
}