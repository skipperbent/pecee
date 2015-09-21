<?php
namespace Pecee\UI\ResponseData;
class ResponseDataFileObject {
	public $name;
	public $size;
	public $type;
	public $error;
	public $tmpName;

	public function move($destination) {
		return move_uploaded_file($this->tmpName, $destination);
	}

	public function getContents() {
		return file_get_contents($this->tmpName);
	}
}