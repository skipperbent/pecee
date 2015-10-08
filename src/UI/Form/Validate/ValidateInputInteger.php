<?php
namespace Pecee\UI\Form\Validate;
use Pecee\PhpInteger;

class ValidateInputInteger extends ValidateInput {

	protected $allowEmpty;
	public function __construct($allowEmpty=false) {
		$this->allowEmpty=$allowEmpty;
	}

	public function validate() {
		return ($this->allowEmpty && empty($this->value) || PhpInteger::isInteger($this->value));
	}
	public function getErrorMessage() {
		return lang('%s is not a valid number', $this->name);
	}
}