<?php
namespace Pecee\UI\Form\Validate;
class ValidateInputCaptcha extends ValidateInput {
	protected $captchaName;
	public function __construct($name) {
		$this->captchaName = $name;
	}
	public function validate() {
		$result = (\Pecee\Session::getInstance()->exists($this->captchaName) && strtolower($this->value) == strtolower(\Pecee\Session::getInstance()->get($this->captchaName)));
		if($result) {
			\Pecee\Session::getInstance()->destroy($this->captchaName);
		}
		return $result;
	}
	public function getErrorMessage() {
		return lang('Invalid captcha verification');
	}
}