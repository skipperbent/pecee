<?php
namespace Pecee\Http\Middleware;

use Pecee\Http\Request;
use Pecee\Traits\BaseApp;

abstract class BaseMiddleware implements IMiddleware
{
    use BaseApp;

    abstract public function handle(Request $request);

}