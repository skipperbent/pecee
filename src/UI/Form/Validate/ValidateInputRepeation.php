<?php
namespace Pecee\UI\Form\Validate;
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
		if( !$this->caseSensitive ) {
			return ((bool)strtolower($this->compareValue) == strtolower($this->value)); 
		} else {
			return ((bool)$this->compareValue == $this->value);
		}
	}
	public function getErrorMessage() {
		return lang('%s is not equal to %s', $this->compareName, $this->name);
	}
}