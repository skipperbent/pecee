<?php
namespace Pecee\Http\Input\Validation;

use Pecee\IO\File;

class ValidateFileAllowedExtension extends ValidateFile {

	protected $extensions;

	public function __construct(array $extensions) {
		$this->extensions=$extensions;
	}

	public function validate() {
		$ext = File::getExtension($this->fileName);
		return (in_array($ext, $this->extensions));
	}

	public function getErrorMessage() {
		return lang('%s is not a valid format', $this->name);
	}

}