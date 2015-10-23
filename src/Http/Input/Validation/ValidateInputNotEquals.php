<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputNotEquals extends ValidateInput {

	protected $notEquals;
	protected $strict;
	protected $error;

	public function __construct($value, $strict=false) {
		$this->notEquals=$value;
		$this->strict=$strict;
	}

	public function validate() {
		if(!$this->strict) {
			$this->value=strtolower($this->value);
			$this->notEquals=strtolower($this->notEquals);
		}
		if($this->value==$this->notEquals) {
			$this->error=lang('%s is required', $this->name);
			return false;
		}
		return true;
	}

	public function getErrorMessage() {
		return $this->error;
	}

}