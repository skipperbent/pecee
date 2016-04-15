<?php
namespace Pecee\Http\InputValidation;

use Pecee\Integer;

class ValidateFileMaxSize extends ValidateFile {

	protected $size;

	public function __construct($sizeKB) {
		if(!Integer::isInteger($sizeKB)) {
			throw new \InvalidArgumentException('Size must be integer');
		}
		$this->size = $sizeKB;
	}

	public function validates() {
		return (($this->size * 1024) >= $this->input->getSize());
	}

	public function getError() {
		return lang('%s cannot be greater than %sKB', $this->input->getName(), $this->input->getSize());
	}

}