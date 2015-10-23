<?php
namespace Pecee\Http\Input\Validation;

class ValidateFileMaxSize extends ValidateFile {

	protected $size;

	public function __construct($sizeKB) {
		if(!\Pecee\Integer::isInteger($sizeKB)) {
			throw new \InvalidArgumentException('Size must be integer');
		}
		$this->size = $sizeKB;
	}

	public function validate() {
		return (($this->size*1024) >= $this->fileSize);
	}

	public function getErrorMessage() {
		return lang('%s cannot be greater than %sKB', $this->name, $this->size);
	}

}