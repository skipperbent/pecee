<?php
namespace Pecee\Model;

use Pecee\Guid;
use Pecee\IO\Directory;
use Pecee\IO\File;
use Pecee\Model\File\FileData;

class ModelFile extends ModelData {

    protected $columns = [
        'id',
        'filename',
        'original_filename',
        'path',
        'type',
        'bytes'
    ];

	public function __construct($name = null, $path = null) {
		$fullPath=null;
		if(!is_null($name) && !is_null($path)) {
			$fullPath = Directory::normalize($path);
		}

		parent::__construct();

        $this->id = Guid::create();
        $this->filename = $name;
        $this->path = $path;

        if($fullPath && is_file($fullPath)) {
            $this->type = File::getMime($fullPath);
            $this->bytes = filesize($fullPath);
        }
	}

	public function setFilename($filename) {
		$this->filename = $filename;
	}

	public function setOriginalFilename($filename) {
		$this->original_filename = $filename;
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function setType($type) {
		$this->type = $type;
	}

	public function setBytes($bytes) {
		$this->bytes = $bytes;
	}

    protected function getDataClass() {
        return FileData::class;
    }

    protected function onNewDataItemCreate(Model &$field) {
        $field->file_id = $this->id;
	    parent::onNewDataItemCreate($field);
    }

	protected function fetchData() {
		return FileData::getByIdentifier($this->id);
	}

	public function getFullPath() {
		return $this->path . Directory::normalize($this->path) . $this->Filename;
	}

}