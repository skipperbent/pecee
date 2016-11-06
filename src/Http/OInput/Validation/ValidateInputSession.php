<?php
namespace Pecee\Http\OInput\Validation;

use Pecee\Session\Session;

class ValidateInputSession extends ValidateInput {

	protected $sessionName;

	public function __construct( $sessionName ) {
		$this->sessionName = $sessionName;
	}

	public function validate() {
		return ((bool)Session::getInstance()->exists($this->sessionName));
	}

	public function getErrorMessage() {
		return lang('%s does not exist', $this->name);
	}

}