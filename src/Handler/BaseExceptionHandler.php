<?php
namespace Pecee\Handler;

use Pecee\Base;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Handlers\IExceptionHandler;

abstract class BaseExceptionHandler extends Base implements IExceptionHandler
{
    abstract public function handleError(Request $request, \Exception $error): void;
}