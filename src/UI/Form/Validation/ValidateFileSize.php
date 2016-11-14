<?php
namespace Pecee\UI\Form\Validation\File;

use Pecee\Http\Input\InputFile;

class ValidateFileSize extends ValidateFile {

    protected $error;
	protected $sizeMinKb;
    protected $sizeMaxKb;

	public function __construct($maxKb, $minKb = null) {
		$this->sizeMinKb = $minKb;
		$this->sizeMaxKb = $maxKb;
	}

	public function validates() {
		if(!($this->input instanceof InputFile)) {
			return true;
		}

		$validates = true;

		if($this->minSize !== null && (($this->size*1024) <= $this->input->getSize())) {
            $this->error = lang('%s cannot be less than %sKB', $this->input->getName(), $this->sizeMinKb);
            $validates = false;
        }

        if((($this->size * 1024) >= $this->input->getSize())) {
            $this->error = lang('%s cannot be greater than %sKB', $this->input->getName(), $this->sizeMaxKb);
            $validates = false;
        }

		return $validates;
	}

	public function getError() {
		return $this->error;
	}

}