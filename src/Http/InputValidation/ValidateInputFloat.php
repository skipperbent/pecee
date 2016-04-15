<?php
namespace Pecee\Http\InputValidation;

use Pecee\FloatUtil;

class ValidateInputFloat extends ValidateInput {

	public function validates() {
		if($this->input->getValue()) {
			return FloatUtil::isFloat(FloatUtil::parse($this->input->getValue()));
		}
		return true;
	}

	public function getError() {
		return lang('%s is not a valid number', $this->input->getValue());
	}

}