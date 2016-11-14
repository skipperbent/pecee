<?php
namespace Pecee\Http\OInput\Validation;

use Carbon\Carbon;

class ValidateInputDate extends ValidateInput {

    protected $format;

    public function __construct($format = null) {
        $this->format = $format;
    }

    public function validate() {

        try {
            if($this->format === null) {
                Carbon::parse($this->input->getValue(), 'UTC');
            } else {
                Carbon::createFromFormat($this->format, $this->input->getValue(), 'UTC');
            }
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    public function getErrorMessage() {
        return lang('%s is not a valid date', $this->input->getName());
    }

}