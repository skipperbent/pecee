<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputFloat extends ValidateInput {

	protected $allowEmpty;

	public function __construct($allowEmpty=false) {
		$this->allowEmpty=$allowEmpty;
	}

	public function validate() {
		return ($this->allowEmpty && empty($this->value) || \Pecee\Float::is_float(\Pecee\Float::ParseFloat($this->value)));
	}

	public function getErrorMessage() {
		return lang('%s is not a valid number', $this->name);
	}

}