<?php
namespace Pecee\Handler;

use Pecee\Base;
use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

abstract class ExceptionHandler extends Base {

	abstract public function handleError( Request $request, RouterEntry $router = null, \Exception $error);

}