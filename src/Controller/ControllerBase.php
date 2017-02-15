<?php
namespace Pecee\Controller;

use Pecee\Exceptions\ValidationException;
use Pecee\Traits\BaseApp;

abstract class ControllerBase
{
    use BaseApp;

    public function __construct()
    {
        debug('START CONTROLLER ' . get_class($this));
        parent::__construct();

        $this->_messages->clear();
    }

    protected function validate(array $validation = null)
    {
        parent::validate($validation);

        if ($this->hasErrors()) {
            $exception = new ValidationException(join(', ', $this->getErrorsArray()), 400);
            $exception->setErrors($this->getErrorsArray());
            throw $exception;
        }
    }

    public function __destruct()
    {
        $this->_messages->clear();
    }

}