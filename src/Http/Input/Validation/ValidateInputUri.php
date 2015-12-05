<?php
namespace Pecee\Http\Input\Validation;

use Pecee\Url;

class ValidateInputUri extends ValidateInput {

	protected $error;

	public function validate() {
		if($this->value && !Url::isValid($this->value)) {
			$this->error = lang('%s is not a valid link', $this->name);
			return false;
		}
		return true;
	}

	public function getErrorMessage() {
		return $this->error;
	}

}