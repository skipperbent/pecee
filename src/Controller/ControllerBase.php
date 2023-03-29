<?php

namespace Pecee\Controller;

use Pecee\Base;
use Pecee\Exceptions\ValidationException;

abstract class ControllerBase extends Base
{

    //protected string $_sessionMessagePrefix = 'controller';

    public function __construct()
    {
        debug('controller', 'Start %s', static::class);
    }

    /**
     * @param array $validation
     * @throws ValidationException
     */
    protected function validate(array $validation): void
    {
        parent::validate($validation);

        if ($this->hasErrors() === true) {
            $exception = new ValidationException(implode(', ', $this->getErrorsArray()), 400);
            $exception->setErrors($this->getErrorsArray());
            $this->sessionMessage()->clear();
            throw $exception;
        }
    }

    public function __destruct()
    {
        $this->sessionMessage()->clear();
    }

}