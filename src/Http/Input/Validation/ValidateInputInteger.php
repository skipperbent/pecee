<?php
namespace Pecee\Http\Input\Validation;

use Pecee\Integer;

class ValidateInputInteger extends ValidateInput {

	protected $allowEmpty;

	public function __construct($allowEmpty=false) {
		$this->allowEmpty=$allowEmpty;
	}

	public function validate() {
		return ($this->allowEmpty && empty($this->value) || Integer::isInteger($this->value));
	}

	public function getErrorMessage() {
		return lang('%s is not a valid number', $this->name);
	}

}