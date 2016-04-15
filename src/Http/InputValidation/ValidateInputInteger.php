<?php
namespace Pecee\Http\InputValidation;

use Pecee\Integer;

class ValidateInputInteger extends ValidateInput {

	public function validates() {
		if($this->input->getValue()) {
			return Integer::isNummeric($this->input->getValue());
		}
		return true;
	}

	public function getError() {
		return lang('%s is not a valid number', $this->input->getName());
	}

}