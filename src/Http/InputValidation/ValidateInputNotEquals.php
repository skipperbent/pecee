<?php
namespace Pecee\Http\InputValidation;

class ValidateInputNotEquals extends ValidateInput {

	protected $notEquals;
	protected $strict;
	protected $error;

	public function __construct($value, $strict=false) {
		$this->notEquals = $value;
		$this->strict = $strict;
	}

	public function validates() {
		if($this->input->getValue()) {
			$value = $this->input->getValue();
			if (!$this->strict) {
				$value = strtolower($value);
				$this->notEquals = strtolower($this->notEquals);
			}

			if ($value === $this->notEquals) {
				$this->error = lang('%s is required', $this->input->getName());
				return false;
			}
		}
		return true;
	}

	public function getError() {
		return $this->error;
	}

}