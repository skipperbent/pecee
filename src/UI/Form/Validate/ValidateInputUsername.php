<?php
namespace Pecee\UI\Form\Validate;
class ValidateInputUsername extends ValidateInput {
	protected $errorMessage;
	protected $minLength;
	protected $maxLength;
	
	public function __construct($minLength=2, $maxLength=25) {
		$this->minLength=$minLength;
		$this->maxLength=$maxLength;
	}
	
	public function validate() {
		if(empty($this->value)) {
			$this->errorMessage = lang('%s is required', $this->name);
		} elseif(strlen($this->value) < $this->minLength) {
			$this->errorMessage = lang('%s is too short', $this->name);
		} elseif(strlen($this->value) > $this->maxLength) {
			$this->errorMessage = lang('%s is too long', $this->name);
		} elseif(!preg_match('/^[a-zA-Z0-9\_\-]+$/', $this->value)) {
			$this->errorMessage = lang('%s contains invalid characters', $this->name);
		}
		return !(isset($this->errorMessage));
	}
	public function getErrorMessage() {
		return $this->errorMessage;
	}
}