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

    protected function validate(array $validation) {
        parent::validate($validation);

        if($this->hasErrors()) {
            throw new \ErrorException(join(', ', $this->getErrorsArray()), 400);
        }
    }

}