<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputPhone extends ValidateInput {

	protected $error;
	protected $length;

	public function __construct($length=8) {
		$this->length=$length;
	}

	public function validate() {
		if(\Pecee\Integer::isInteger($this->value))
			$this->error = lang('%s is not a valid number', $this->name);
		elseif(strlen($this->value) == $this->length)
			return true;
		else
			$this->error = lang('%s has to contain %s numbers', $this->name, $this->length);
		return false;
	}

	public function getErrorMessage() {
		return $this->error;
	}

}