<?php
namespace Pecee\UI\Form\Validation;

class ValidateNotNullOrEmpty extends ValidateInput {

	public function validates() {
		return (!empty(trim($this->input->getValue())));
	}

	public function getError() {
		return lang('%s is required', $this->input->getName());
	}

}