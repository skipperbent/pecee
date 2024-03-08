<?php

namespace Pecee\Controller;

use Pecee\Base;
use Pecee\Exceptions\ValidationException;

abstract class ControllerBase extends Base
{

    public function __construct()
    {
        debug('controller', 'Start %s', static::class);
    }

    /**
     * @param array $validation
     * @throws ValidationException
     */
    public function validate(array $validation): void
    {
        parent::validate($validation);

        if ($this->hasErrors() === true) {
            $exception = new ValidationException(implode(', ', $this->getErrorsArray()), 400);
            $exception->setErrors($this->getErrorsArray());
            $this->sessionMessage()->clear();
            throw $exception;
        }
    }

}