<?php
namespace Pecee\UI\Form\Validate;
class ValidateInputEmail extends ValidateInput {
	public function validate() {
		return \Pecee\Util::is_email($this->value);
	}
	public function getErrorMessage() {
		return lang('%s is not a valid email', $this->name);
	}
}