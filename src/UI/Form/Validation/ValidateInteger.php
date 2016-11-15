<?php
namespace Pecee\UI\Form\Validation;

class ValidateInteger extends ValidateInput {

	public function validates() {
		if($this->input->getValue()) {
			return \Pecee\Integer::isNummeric($this->input->getValue());
		}
		return true;
	}

	public function getError() {
		return lang('%s is not a valid number', $this->input->getName());
	}

}