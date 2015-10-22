<?php
namespace Pecee\Controller;

use Pecee\Base;
use Pecee\Debug;

abstract class Controller extends Base {

	public function __construct() {
		Debug::getInstance()->add('START CONTROLLER ' . get_class($this));
		parent::__construct();
	}

	public function asJson(array $array) {
		return response()->cache()->json($array);
	}

}