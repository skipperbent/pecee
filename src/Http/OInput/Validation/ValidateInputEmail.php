<?php
namespace Pecee\Http\OInput\Validation;
use Pecee\Util;

class ValidateInputEmail extends ValidateInput {

	public function validate() {
		if($this->value) {
			return Util::is_email($this->value);
		}
		return true;
	}

	public function getErrorMessage() {
		return lang('%s is not a valid email', $this->name);
	}

}