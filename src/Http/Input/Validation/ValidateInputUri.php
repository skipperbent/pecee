<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputUri extends ValidateInput {

	protected $error;

	public function validate() {
		if(empty($this->value)) {
			$this->error = lang('%s is required', $this->name);
			return false;
		} elseif(!\Pecee\Url::IsValid($this->value)) {
			$this->error = lang('%s is not a valid link', $this->name);
			return false;
		}
		return true;
	}

	public function getErrorMessage() {
		return $this->error;
	}

}