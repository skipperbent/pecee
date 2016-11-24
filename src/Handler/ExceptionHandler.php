<?php
namespace Pecee\Handler;

use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

abstract class ExceptionHandler implements IExceptionHandler
{

	abstract public function handleError(Request $request, ILoadableRoute &$route = null, \Exception $error);

}