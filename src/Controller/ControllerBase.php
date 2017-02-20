<?php
namespace Pecee\Controller;

use Pecee\Base;
use Pecee\Exceptions\ValidationException;

abstract class ControllerBase extends Base
{

    public function __construct()
    {
        debug('START CONTROLLER ' . static::class);
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