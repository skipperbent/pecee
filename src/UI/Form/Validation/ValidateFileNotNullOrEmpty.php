<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Http\Input\InputFile;

class ValidateFileNotNullOrEmpty extends ValidateFile
{

	public function validates()
	{
		if (!($this->input instanceof InputFile)) {
			return false;
		}

		return ($this->input->hasError() === false && !empty($this->input->getName()) && $this->input->getSize() > 0 && $this->input->getError() == 0);
	}

	public function getError()
	{
		return lang('%s cannot be empty', [$this->input->getName()]);
	}

}