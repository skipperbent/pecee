<?php
namespace Pecee\Controller;

use Pecee\Base;
use Pecee\Debug;

abstract class ControllerBase extends Base {

	public function __construct() {
		Debug::getInstance()->add('START CONTROLLER ' . get_class($this));
		parent::__construct();

		$this->_messages->clear();
	}

}