<?php
namespace Pecee\Http\Input\Validation;

use Pecee\Integer;

class ValidateInputInteger extends ValidateInput {

	public function validate() {
		if($this->value) {
			return Integer::isNummeric($this->value);
		}
		return true;
	}

	public function getErrorMessage() {
		return lang('%s is not a valid number', $this->name);
	}

}