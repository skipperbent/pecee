<?php
namespace Pecee\Http\OInput;

use Pecee\Collection\CollectionItem;
use Pecee\Http\OInput\Validation\IValidateFile;
use Pecee\IO\File;

class InputFile extends CollectionItem implements IInputItem {

    protected $validationErrors = array();
    protected $validations = array();

    public function validates() {
        if(count($this->validations)) {
            /* @var $validation \Pecee\Http\OInput\Validation\ValidateInput */
            foreach($this->validations as $validation) {
                if(!$validation->validate()) {
                    $this->validationErrors[] = $validation;
                }
            }
        }
        return (count($this->validationErrors) === 0);
    }

    public function addValidation($validation) {
        if(is_array($validation)) {
            $this->validations = array();

            foreach($validation as $v) {

                if(!($v instanceof IValidateFile)) {
                    throw new \ErrorException('Validation type must be an instance of ValidateInput - type given: ' . get_class($v));
                }

                $v->setFileName($this->name);
                $v->setFileType($this->type);
                $v->setFileTmpName($this->tmpName);
                $v->setFileError($this->error);
                $v->setFileSize($this->size);

                $this->validations[] = $v;
            }
            return;
        }

        if(!($validation instanceof IValidateFile)) {
            throw new \ErrorException('Validation type must be an instance of ValidateInput - type given: ' . get_class($validation));
        }

        $validation->setFileName($this->name);
        $validation->setFileType($this->type);
        $validation->setFileTmpName($this->tmpName);
        $validation->setFileError($this->error);
        $validation->setFileSize($this->size);

        $this->validations = array($validation);
    }

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @return string
	 */
	public function getTmpName() {
		return $this->tmpName;
	}

    public function getExtension() {
        return File::getExtension($this->getName());
    }

	public function move($destination) {
		return move_uploaded_file($this->tmpName, $destination);
	}

	public function getContents() {
		return file_get_contents($this->tmpName);
	}

    /**
     * @return array
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }

}