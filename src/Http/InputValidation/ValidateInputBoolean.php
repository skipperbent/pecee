<?php
namespace Pecee\Http\InputValidation;

class ValidateInputBoolean extends ValidateInput {

	public function validates() {
		if($this->input->getValue()) {
			return ($this->input->getValue() == true || $this->input->getValue() == false);
		}
		return true;
	}

	public function getError() {
		return lang('%s must be true or false', $this->input->getName());
	}

}