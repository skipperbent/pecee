<?php
namespace Pecee\Http\InputValidation;

use Pecee\Session\Session;

class ValidateInputSession extends ValidateInput {

	protected $sessionName;

	public function __construct( $sessionName ) {
		$this->sessionName = $sessionName;
	}

	public function validates() {
		return ((bool)Session::exists($this->sessionName));
	}

	public function getError() {
		return lang('%s does not exist', $this->input->getName());
	}

}