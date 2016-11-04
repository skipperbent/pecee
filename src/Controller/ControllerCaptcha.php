<?php
namespace Pecee\Controller;

use Pecee\Session\Session;
use Pecee\UI\Form\FormCaptcha;

class ControllerCaptcha extends Controller {

	public function show($captchaName) {
		if(Session::exists($captchaName . '_data')) {
			$captcha = Session::get($captchaName . '_data');
			if($captcha instanceof FormCaptcha) {
				$captcha->showCaptcha();
                Session::destroy($captchaName . '_data');
			}
		}
	}

}