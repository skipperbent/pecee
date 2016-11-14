<?php
namespace Pecee\Controller;

abstract class Controller {

	public function __construct() {
        request()->debug->add('START CONTROLLER ' . get_class($this));
	}

}