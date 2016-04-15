<?php
namespace Pecee\Http\InputValidation;

class ValidateInputNotNullOrEmpty extends ValidateInput {

	public function validates() {
		return (!empty($this->input->getValue()));
	}

	public function getError() {
		return lang('%s is required', $this->input->getName());
	}

}