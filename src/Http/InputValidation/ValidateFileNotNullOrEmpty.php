<?php
namespace Pecee\Http\InputValidation;

use Pecee\Http\Input\InputFile;

class ValidateFileNotNullOrEmpty extends ValidateFile {

	public function validates() {
		if(!($this->input instanceof InputFile)) {
			return false;
		}
		return (!empty($this->input->getName()) && $this->input->getSize() > 0 && $this->input->getError() == 0);
	}

	public function getError() {
		return lang('%s cannot be empty', array($this->input->getName()));
	}

}