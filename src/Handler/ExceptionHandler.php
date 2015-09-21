<?php
namespace Pecee\Handler;

abstract class ExceptionHandler {

	abstract public function handleError(\Exception $error);

}