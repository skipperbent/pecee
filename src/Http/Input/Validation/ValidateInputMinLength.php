<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputMinLength extends ValidateInput {

	protected $minimumLength;

	public function __construct($minimumLength = 5) {
		$this->minimumLength = $minimumLength;
	}

	public function validate() {
		if($this->value) {
			return ((strlen($this->value) > $this->minimumLength));
		}
		return true;
	}

	public function getErrorMessage() {
		return lang('%s has to minimum %s characters long', $this->name, $this->minimumLength);
	}

}