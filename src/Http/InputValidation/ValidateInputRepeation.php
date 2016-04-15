<?php
namespace Pecee\Http\InputValidation;

class ValidateInputRepeation extends ValidateInput {

	protected $compareName;
	protected $compareValue;
	protected $caseSensitive;

	public function __construct( $compareName, $compareValue, $caseSensitive = true ) {
		$this->compareName = $compareName;
		$this->compareValue = $compareValue;
		$this->caseSensitive = $caseSensitive;
	}

	public function validates() {
		if($this->input->getValue()) {
			if (!$this->caseSensitive) {
				return (strtolower($this->compareValue) === strtolower($this->input->getValue()));
			}
			return ($this->compareValue === $this->input->getValue());
		}
		return true;
	}

	public function getError() {
		return lang('%s is not equal to %s', $this->compareName, $this->input->getName());
	}

}