<?php
namespace Pecee\Controller;

use Pecee\Base;
use Pecee\Exceptions\ValidationException;

abstract class ControllerBase extends Base
{

    public function __construct()
    {
        debug('START CONTROLLER %s', static::class);
    }

    /**
     * @param array $validation
     * @throws ValidationException
     */
    protected function validate(array $validation)
    {
        parent::validate($validation);

        if ($this->hasErrors() === true) {
            $exception = new ValidationException(implode(', ', $this->getErrorsArray()), 400);
            $exception->setErrors($this->getErrorsArray());
            throw $exception;
        }
    }

    public function __destruct()
    {
        $this->sessionMessage()->clear();
    }

}