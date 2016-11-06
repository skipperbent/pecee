<?php
namespace Pecee\Http\OInput\Validation;

use Pecee\FloatUtil;

class ValidateInputFloat extends ValidateInput {

	public function validate() {
		if($this->value) {
			return FloatUtil::isFloat(FloatUtil::parse($this->value));
		}
		return true;
	}

	public function getErrorMessage() {
		return lang('%s is not a valid number', $this->name);
	}

}