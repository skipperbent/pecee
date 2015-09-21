<?php
namespace Pecee\UI\Form\Validate;
use Pecee\File;

class ValidateFileAllowedExtension extends ValidateFile {
	protected $extensions;
	public function __construct(array $extensions) {
		$this->extensions=$extensions;
	}

	public function validate() {
		$ext = File::GetExtension($this->fileName);
		return (in_array($ext, $this->extensions));
	}

	public function getErrorMessage() {
		return lang('%s is not a valid format', $this->name);
	}
}