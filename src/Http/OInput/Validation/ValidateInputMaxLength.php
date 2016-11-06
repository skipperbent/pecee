<?php
namespace Pecee\Http\OInput\Validation;
class ValidateInputMaxLength extends ValidateInput {

	protected $maxLength;

	public function __construct($maxLength = 50) {
		$this->maxLength = $maxLength;
	}

	public function validate() {
		if($this->value) {
			return !(strlen($this->value) > $this->maxLength);
		}
		return true;
	}

	public function getErrorMessage() {
		return lang('%s can only be %s characters', $this->name, $this->maxLength);
	}

}