<?php
namespace Pecee\Controller;
use Pecee\Session\Session;
use Pecee\UI\Form\FormCaptcha;

class ControllerCaptcha extends Controller {

	public function getShow($captchaName) {
        $session = Session::getInstance();
		if($session->exists($captchaName . '_data')) {
			$captcha = $session->get($captchaName . '_data');
			if($captcha instanceof FormCaptcha) {
				$captcha->showCaptcha();
                $session->destroy($captchaName . '_data');
			}
		}
	}

}