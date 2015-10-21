<?php
namespace Pecee\UI\ResponseData;
class ResponseDataFile extends ResponseData {
	public function __construct() {
		parent::__construct();
		if(isset($_FILES)) {
			foreach($_FILES as $key=>$value) {
				// Multiple files
				if(is_array($value['name'])) {
					foreach($value['name'] as $k=>$val) {
						// Strip empty values
						if($value['error'][$k] != '4') {
							$file=new ResponseDataFileObject();
							$file->name = $value['name'][$k];
							$file->size = $value['size'][$k];
							$file->type = $value['type'][$k];
							$file->tmpName = $value['tmp_name'][$k];
							$file->error = $value['error'][$k];
							$this->data[strtolower($key)][$k] = $file;
						}
					}
				} else {
					// Strip empty values
					if($value['error'] != '4') {
						$file = new ResponseDataFileObject();
						$file->name = $value['name'];
						$file->size = $value['size'];
						$file->type = $value['type'];
						$file->tmpName = $value['tmp_name'];
						$file->error = $value['error'];
						$this->data[strtolower($key)] = $file;
					}
				}
			}
		}
	}

	/**
	 * Adds validation input
	 * @param string $name
	 * @param string $index
	 * @param \Pecee\UI\Form\Validate\ValidateInput|array $type
	 * @throws \ErrorException
	 */
	public function addInputValidation($name, $index, $type) {
		if($type == 'Pecee\\UI\\Form\\Validate\\IValidateFile') {
			throw new \ErrorException('Unknown validate type. Must be of type \Pecee\UI\Form\Validate\IValidateFile');
		}
		if(strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
			$file = $this->__get($index);
			if(is_array($type)) {
				$types = array();
				/* @var $t \Pecee\UI\Form\Validate\ValidateFile */
				foreach($type as $t) {
					$t->setIndex($index);
					$t->setName($name);
					if($file) {
						$t->setFileName($file->name);
						$t->setFileType($file->type);
						$t->setFileTmpName($file->tmpName);
						$t->setFileError($file->error);
						$t->setFileSize($file->size);
					}
					$types[] = $t;
				}
				$this->validateInput($types);
			} else {
				$type->setIndex($index);
				$type->setName($name);
				if($file) {
					$type->setFileName($file->name);
					$type->setFileType($file->type);
					$type->setFileTmpName($file->tmpName);
					$type->setFileError($file->error);
					$type->setFileSize($file->size);
				}
				$this->validateInput($type);
			}
		}
	}
}