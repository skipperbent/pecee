<?php
namespace Pecee\UI\Form\Validate;
class ValidateInputMaxValue extends ValidateInput {
	protected $maxValue;
	protected $error;
	public function __construct($maxValue) {
		$this->maxValue = $maxValue;
	}

	public function validate() {
		if(!\Pecee\Integer::isInteger($this->value)) {
			$this->error = lang('%s is not a valid number', $this->name);
		}
		if($this->value > $this->maxValue) {
			$this->error = lang('%s cannot be greater than %s', $this->name, $this->maxValue);
		}
		return !($this->error);
	}

	public function getErrorMessage() {
		return $this->error;
	}

}