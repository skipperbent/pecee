<?php
namespace Pecee\Handler;

use Pecee\Base;
use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Request;

abstract class BaseExceptionHandler extends Base implements IExceptionHandler
{
    abstract public function handleError(Request $request, \Exception $error): void;
}