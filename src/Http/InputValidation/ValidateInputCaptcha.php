<?php
namespace Pecee\Http\InputValidation;

use Pecee\Session\Session;

class ValidateInputCaptcha extends ValidateInput {

	protected $captchaName;

	public function __construct($name) {
		$this->captchaName = $name;
	}

	public function validates() {
		$result = (Session::getInstance()->exists($this->captchaName) && strtolower($this->input->getValue()) == strtolower(Session::getInstance()->get($this->captchaName)));
		if($result) {
			Session::getInstance()->destroy($this->captchaName);
		}
		return $result;
	}

	public function getError() {
		return lang('Invalid captcha verification');
	}

}