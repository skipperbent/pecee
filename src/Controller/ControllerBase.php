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
    }

    protected function validate(array $validation)
    {
        parent::validate($validation);

        if ($this->hasErrors() === true) {
            $exception = new ValidationException(join(', ', $this->getErrorsArray()), 400);
            $exception->setErrors($this->getErrorsArray());
            throw $exception;
        }
    }

}