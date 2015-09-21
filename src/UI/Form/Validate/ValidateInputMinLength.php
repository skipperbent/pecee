<?php
namespace Pecee\UI\Form\Validate;
class ValidateInputMinLength extends ValidateInput {
	protected $minimumLength;
	public function __construct($minimumLength = 5) {
		$this->minimumLength = $minimumLength;
	}
	public function validate() {
		return ((strlen($this->value) > $this->minimumLength));
	}
	public function getErrorMessage() {
		return lang('%s has to minimum %s characters long', $this->name, $this->minimumLength);
	}
}