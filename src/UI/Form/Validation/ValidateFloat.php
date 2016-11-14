<?php
namespace Pecee\UI\Form\Validation;

use Pecee\FloatUtil;

class ValidateFloat extends ValidateInput {

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