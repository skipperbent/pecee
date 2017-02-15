<?php
namespace Pecee\Handler;

use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Request;
use Pecee\Traits\BaseApp;

abstract class BaseExceptionHandler implements IExceptionHandler
{
    use BaseApp;

    abstract public function handleError(Request $request, \Exception $error);
}