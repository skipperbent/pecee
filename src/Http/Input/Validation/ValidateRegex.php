<?php
namespace Pecee\Http\Input\Validation;

class ValidateRegex extends ValidateInput {

    protected $regex;
    protected $errorMessage;

    public function __construct($regex, $errorMessage) {
        $this->regex = $regex;
        $this->errorMessage = $errorMessage;
    }

    public function validate() {
        return (preg_match($this->regex, $this->value) === 0);
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }

}