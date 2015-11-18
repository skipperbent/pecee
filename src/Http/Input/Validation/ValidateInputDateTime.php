<?php
namespace Pecee\Http\Input\Validation;

use Pecee\Date;

class ValidateInputDateTime extends ValidateInput {

	public function validate() {
		return (empty($this->value) || Date::isDate($this->value));
	}

	public function getErrorMessage() {
		return lang('%s is not a valid date time', $this->name);
	}

}