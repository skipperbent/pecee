<?php
namespace Pecee\Handler;

use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

abstract class ExceptionHandler implements IExceptionHandler {

	abstract public function handleError(Request $request, RouterEntry &$route = null, \Exception $error);

}