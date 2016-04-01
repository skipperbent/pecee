<?php
namespace Pecee\Http\Input\Validation;

abstract class ValidateFile extends ValidateInput {

	protected $fileName;
	protected $fileType;
	protected $fileSize;
	protected $fileTmpName;
	protected $fileError;

	public function getFileName() {
		return $this->fileName;
	}

	public function getFileType() {
		return $this->fileType;
	}

	public function getFileSize() {
		return $this->fileSize;
	}

	public function getFileTmpName() {
		return $this->fileTmpName;
	}

	public function getFileError() {
		return $this->fileError;
	}

	public function setFileName($fileName) {
		$this->fileName = $fileName;
	}

	public function setFileType($fileType) {
		$this->fileType = $fileType;
	}
	
	public function setFileSize($fileSize) {
		$this->fileSize = $fileSize;
	}

	public function setFileTmpName($fileTmpName) {
		$this->fileTmpName = $fileTmpName;
	}

	public function setFileError($fileError) {
		$this->fileError = $fileError;
	}

}