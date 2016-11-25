<?php
namespace Pecee\Handler;

use Pecee\Base;
use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

abstract class BaseExceptionHandler extends Base implements IExceptionHandler
{

    abstract public function handleError(Request $request, ILoadableRoute &$route = null, \Exception $error);

}