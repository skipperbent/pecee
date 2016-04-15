<?php
namespace Pecee\Http\InputValidation;
use Pecee\Util;

class ValidateInputEmail extends ValidateInput {

	public function validates() {
		if($this->input->getValue()) {
			return Util::is_email($this->input->getValue());
		}
		return true;
	}

	public function getError() {
		return lang('%s is not a valid email', $this->input->getName());
	}

}