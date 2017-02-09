<?php
namespace Pecee\Http\Middleware;

use Pecee\Http\Request;

abstract class Middleware implements IMiddleware
{

    abstract public function handle(Request $request);

}