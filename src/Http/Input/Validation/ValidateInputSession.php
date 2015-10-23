<?php
namespace Pecee\Http\Input\Validation;

class ValidateInputSession extends ValidateInput {

	protected $sessionName;

	public function __construct( $sessionName ) {
		$this->sessionName = $sessionName;
	}

	public function validate() {
		return ((bool)\Pecee\Session::getInstance()->exists($this->sessionName));
	}

	public function getErrorMessage() {
		return lang('%s does not exist', $this->name);
	}

}