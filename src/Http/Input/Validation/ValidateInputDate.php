<?php
namespace Pecee\Http\Input\Validation;

use Carbon\Carbon;

class ValidateInputDate extends ValidateInput {

	protected $format;

	public function __construct($format = null) {
		$this->format = $format;
	}

	public function validate() {

		try {
			if($this->format === null) {
				Carbon::parse($this->value, 'UTC');
			} else {
				Carbon::createFromFormat($this->format, $this->value, 'UTC');
			}
		} catch(\Exception $e) {
			return false;
		}

		return true;
	}

	public function getErrorMessage() {
		return lang('%s is not a valid date', $this->name);
	}

}