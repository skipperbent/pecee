<?php
namespace Pecee\Http\OInput\Validation;
use Pecee\Date;

class ValidateInputDate extends ValidateInput {

	protected $error;
	protected $format;

	public function __construct($format = 'Y-m-d H:i:s') {
		$this->format = $format;
	}

	public function validate() {
		if($this->value) {
			return Date::isValid($this->value, $this->format);
		}
		return true;
	}

	public function getErrorMessage() {
		return lang('%s is not a valid date', $this->name);
	}

}