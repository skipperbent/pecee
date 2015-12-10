<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputRepeation extends ValidateInput {

	protected $compareName;
	protected $compareValue;
	protected $caseSensitive;

	public function __construct( $compareName, $compareValue, $caseSensitive = true ) {
		$this->compareName = $compareName;
		$this->compareValue = $compareValue;
		$this->caseSensitive = $caseSensitive;
	}

	public function validate() {
		if($this->value) {
			if (!$this->caseSensitive) {
				return (strtolower($this->compareValue) === strtolower($this->value));
			} else {
				return ($this->compareValue === $this->value);
			}
		}
		return true;
	}

	public function getErrorMessage() {
		return lang('%s is not equal to %s', $this->compareName, $this->name);
	}

}