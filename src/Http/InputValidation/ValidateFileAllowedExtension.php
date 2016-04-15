<?php
namespace Pecee\Http\InputValidation;

use Pecee\IO\File;

class ValidateFileAllowedExtension extends ValidateFile {

	protected $extensions;

	public function __construct(array $extensions) {
		$this->extensions = $extensions;
	}

	public function validates() {
		$ext = File::getExtension($this->input->getName());
		return (in_array($ext, $this->extensions));
	}

	public function getError() {
		return lang('%s is not a valid format', $this->input->getName());
	}

}