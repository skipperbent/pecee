<?php
namespace Pecee\Http\InputValidation;

use Pecee\Http\Input\InputFile;

class ValidateFileAllowedMimeType extends ValidateFile {

	protected $mimeTypes;

	public function __construct(array $mimeTypes) {
		$this->mimeTypes = $mimeTypes;
	}

	public function validates() {

		if(!($this->input instanceof InputFile)) {
			return true;
		}

		return (in_array(strtolower($this->input->getType()), $this->mimeTypes));
	}

	public function getError() {
		return lang('%s is not a valid format', array($this->input->getName()));
	}

}