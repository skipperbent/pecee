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

    /**
     * @param array $validation
     * @throws ValidationException
     * @throws \RuntimeException
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

    /**
     * @throws \RuntimeException
     */
    public function __destruct()
    {
        $this->_messages->clear();
    }

}