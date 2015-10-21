<?php
namespace Pecee\Model;
use Pecee\Date;
use Pecee\DB\DBTable;
use Pecee\IO\Directory;
use Pecee\IO\File;
use Pecee\Model\File\FileData;

class ModelFile extends ModelData {
	const ORDER_DATE_ASC = 'f.`createdDate` ASC';
	const ORDER_DATE_DESC = 'f.`createdDate` DESC';

	public static $ORDERS = array(self::ORDER_DATE_ASC, self::ORDER_DATE_DESC);

	public function __construct($name = null, $path = null) {
		$fullPath=null;
		if(!is_null($name) && !is_null($path)) {
			$fullPath = Directory::normalize($path);
		}

        $table = new DBTable();
        $table->column('fileId')->string(40)->primary();
        $table->column('filename')->string(355)->index();
        $table->column('originalFilename')->string(355)->index();
        $table->column('path')->string(355)->index();
        $table->column('type')->string(255)->index();
        $table->column('bytes')->integer()->index();
        $table->column('createdDate')->datetime()->index();

		parent::__construct($table);

        $this->fileId = \Pecee\Guid::create();
        $this->filename = $name;
        $this->path = $path;

        if($fullPath && is_file($fullPath)) {
            $this->type = File::getMime($fullPath);
            $this->bytes = filesize($fullPath);
        }

        $this->createdDate = Date::toDateTime();
	}

	public function setFilename($filename) {
		$this->filename = $filename;
	}

	public function setOriginalFilename($filename) {
		$this->originalFilename = $filename;
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

	public function setCreatedDate($datetime) {
		$this->createdDate = $datetime;
	}

	public function updateData() {
		if($this->data) {
			/* Remove all fields */
			FileData::removeAll($this->fileId);
			foreach($this->data->getData() as $key=>$value) {
				$data=new FileData($this->fileId, $key, $value);
				$data->save();
			}
		}
	}

	protected function fetchData() {
		$data = FileData::getFileId($this->fileId);
		if($data->hasRows()) {
			foreach($data->getRows() as $d) {
				$this->setDataValue($d->getKey(), $d->getValue());
			}
		}
	}

	public function getFullPath() {
		return $this->path . Directory::normalize($this->path) . $this->Filename;
	}

	/**
	 * Get file by file id.
	 * @param string $fileId
	 * @return self
	 */
	public static function getById($fileId){
		return self::fetchOne('SELECT * FROM {table} WHERE `fileId` = %s', array($fileId));
	}

	public static function get($order=null, $rows=null, $page=null){
		$order = (in_array($order, self::$ORDERS)) ? $order : self::ORDER_DATE_DESC;
		return self::fetchPage('SELECT f.* FROM {table} f ORDER BY ' .$order, $rows=null,$page=null);
	}
}