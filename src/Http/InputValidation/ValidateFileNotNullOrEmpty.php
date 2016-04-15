<?php
namespace Pecee\Http\InputValidation;

class ValidateFileNotNullOrEmpty extends ValidateFile {

	public function validates() {
		return (!empty($this->input->getName()) && $this->input->getSize() > 0 && $this->input->getError() == 0);
	}

	public function getError() {
		return lang('%s cannot be empty', array($this->input->getName()));
	}

}