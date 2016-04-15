<?php
namespace Pecee\Http\InputValidation;

class ValidateFileAllowedMimeType extends ValidateFile {

	protected $mimeTypes;

	public function __construct(array $mimeTypes) {
		$this->mimeTypes = $mimeTypes;
	}

	public function validates() {
		return (in_array(strtolower($this->input->getType()), $this->mimeTypes));
	}

	public function getError() {
		return lang('%s is not a valid format', array($this->input->getName()));
	}

}