<?php
namespace Pecee\UI\Form\Validate;
class ValidateInputZip extends ValidateInput {
	protected $error;
	protected $length;
	public function __construct($length=4) {
		$this->length=$length;
	}
	public function validate() {
		if(\Pecee\PhpInteger::isInteger($this->value))
			$this->error = lang('%s can only contain numbers', $this->name);
		elseif(strlen($this->value) == $this->length)
			return true;
		else
			$this->error = lang('%s should be %s characters long', $this->name, $this->length);
		return false;
	}
	public function getErrorMessage() {
		return $this->error;
	}
}