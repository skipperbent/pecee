<?php
namespace Pecee\Http\InputValidation;

class ValidateInputUsername extends ValidateInput {

	protected $errorMessage;
	protected $minLength;
	protected $maxLength;

	public function __construct($minLength = 2, $maxLength = 25) {
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;
	}

	public function validates() {
		if($this->input->getValue()) {
			if (strlen($this->input->getValue()) < $this->minLength) {
				$this->errorMessage = lang('%s is too short', $this->input->getName());
			} elseif (strlen($this->input->getValue()) > $this->maxLength) {
				$this->errorMessage = lang('%s is too long', $this->input->getName());
			} elseif (!preg_match('/^[a-zA-Z0-9\_\-]+$/', $this->input->getValue())) {
				$this->errorMessage = lang('%s contains invalid characters', $this->input->getName());
			}
			return !(isset($this->errorMessage));
		}
		return true;
	}

	public function getError() {
		return $this->errorMessage;
	}

}