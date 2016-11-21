<?php
namespace Pecee\Http\Middleware;

use Pecee\Base;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

abstract class BaseMiddleware extends Base implements IMiddleware {

    abstract public function handle(Request $request, ILoadableRoute &$route);

}