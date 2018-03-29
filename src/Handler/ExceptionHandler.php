<?php

namespace Pecee\Handler;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Handlers\IExceptionHandler;

abstract class ExceptionHandler implements IExceptionHandler
{
    abstract public function handleError(Request $request, \Exception $error): void;
}