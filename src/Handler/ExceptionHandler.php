<?php

namespace Pecee\Handler;

use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Request;

abstract class ExceptionHandler implements IExceptionHandler
{
    abstract public function handleError(Request $request, \Exception $error): void;
}