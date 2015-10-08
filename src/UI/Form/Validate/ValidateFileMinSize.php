<?php
namespace Pecee\UI\Form\Validate;
class ValidateFileMinSize extends ValidateFile {
	protected $size;
	public function __construct($sizeKB) {
		if(!\Pecee\PhpInteger::isInteger($sizeKB)) {
			throw new \InvalidArgumentException('Size must be integer');
		}
		$this->size = $sizeKB;
	}
	
	public function validate() {
		return (($this->size*1024) >= $this->fileSize);
	}
	
	public function getErrorMessage() {
		return lang('%s cannot be less than %sKB', $this->size);
	}	
}