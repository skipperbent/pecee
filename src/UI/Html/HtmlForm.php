<?php
namespace Pecee\UI\Html;

use Pecee\Http\Middleware\BaseCsrfVerifier;

class HtmlForm extends Html {

	public function __construct($name, $method, $action, $enctype) {
		parent::__construct('form');
		$this->closingType = self::CLOSE_TYPE_NONE;
		$this->addAttribute('name', $name);
		$this->addAttribute('enctype', $enctype);
		$this->addAttribute('method', $method);
		$this->addAttribute('action', ((!$action) ? url() : $action));

		// Add csrf token
		if(strtolower($method) !== 'get') {
			$this->addItem(new HtmlInput(BaseCsrfVerifier::POST_KEY, 'hidden', csrf_token()));
		}

	}
}