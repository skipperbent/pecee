<?php
namespace Pecee\Controller;

use Pecee\Base;

abstract class ControllerBase extends Base {

    public function __construct() {
        debug('START CONTROLLER ' . get_class($this));
        parent::__construct();
    }

    public function __destruct()
    {
        $this->_messages->clear();
    }

}