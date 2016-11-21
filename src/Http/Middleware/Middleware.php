<?php
namespace Pecee\Http\Middleware;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

abstract class Middleware implements IMiddleware {

    abstract public function handle(Request $request, ILoadableRoute &$route);

}