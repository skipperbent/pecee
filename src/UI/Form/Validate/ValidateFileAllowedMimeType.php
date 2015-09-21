<?php
namespace Pecee\UI\Form\Validate;
class ValidateFileAllowedMimeType extends ValidateFile {
	protected $mimeTypes;
	public function __construct(array $mimeTypes) {
		$this->mimeTypes=$mimeTypes;
	}
	
	public function validate() {
		return (in_array(strtolower($this->fileType), $this->mimeTypes));
	}

	public function getErrorMessage() {
		return lang('%s is not a valid format', array($this->name));
	}
}