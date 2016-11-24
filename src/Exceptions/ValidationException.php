<?php
namespace Pecee\Exceptions;

class ValidationException extends \Exception
{

	protected $errors;

	public function setErrors(array $errors)
	{
		$this->errors = $errors;
	}

	public function getErrors()
	{
		return $this->errors;
	}

}