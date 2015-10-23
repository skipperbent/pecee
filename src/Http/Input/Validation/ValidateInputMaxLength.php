<?php
namespace Pecee\Http\Input\Validation;
class ValidateInputMaxLength extends ValidateInput {

	protected $maxLength;

	public function __construct($maxLength = 50) {
		$this->maxLength = $maxLength;
	}

	public function validate() {
		return !(strlen($this->value) > $this->maxLength);
	}

	public function getErrorMessage() {
		return lang('%s can only be %s characters', $this->name, $this->maxLength);
	}

}