<?php

namespace Pecee\Http\Middleware;

use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

abstract class Middleware implements IMiddleware {

    abstract public function handle(Request $request, RouterEntry &$route);

}