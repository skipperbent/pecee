<?php

namespace Pecee\Http\Middleware;

use Pecee\Base;
use Pecee\Http\Request;

abstract class BaseMiddleware extends Base implements IMiddleware
{
    abstract public function handle(Request $request);
}