<?php
namespace Pecee\Http\OInput\Validation;

use Pecee\Session\Session;

class ValidateInputCaptcha extends ValidateInput {

	protected $captchaName;

	public function __construct($name) {
		$this->captchaName = $name;
	}

	public function validate() {
		$result = (Session::getInstance()->exists($this->captchaName) && strtolower($this->value) == strtolower(Session::getInstance()->get($this->captchaName)));
		if($result) {
			Session::getInstance()->destroy($this->captchaName);
		}
		return $result;
	}

	public function getErrorMessage() {
		return lang('Invalid captcha verification');
	}

}