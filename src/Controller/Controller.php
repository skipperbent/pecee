<?php
namespace Pecee\Controller;

use Pecee\Base;
use Pecee\Debug;

abstract class Controller {

	public function __construct() {
		Debug::getInstance()->add('START CONTROLLER ' . get_class($this));
	}

}