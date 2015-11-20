<?php
namespace Pecee\Http\Input\Validation;

use Pecee\FloatUtil;

class ValidateInputFloat extends ValidateInput {

	protected $allowEmpty;

	public function __construct($allowEmpty=false) {
		$this->allowEmpty = $allowEmpty;
	}

	public function validate() {
		return ($this->allowEmpty && empty($this->value) || FloatUtil::isFloat(FloatUtil::parse($this->value)));
	}

	public function getErrorMessage() {
		return lang('%s is not a valid number', $this->name);
	}

}