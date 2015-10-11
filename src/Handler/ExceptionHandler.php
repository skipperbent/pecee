<?php
namespace Pecee\Handler;

abstract class ExceptionHandler {

	abstract public function handleError(\RouterEntry $router = null, \Exception $error);

}