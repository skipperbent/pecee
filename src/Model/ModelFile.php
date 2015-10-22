<?php
namespace Pecee\Model;
use Pecee\Date;
use Pecee\DB\DBTable;
use Pecee\Guid;
use Pecee\IO\Directory;
use Pecee\IO\File;
use Pecee\Model\File\FileData;

class ModelFile extends ModelData {
	const ORDER_DATE_ASC = 'f.`created_date` ASC';
	const ORDER_DATE_DESC = 'f.`created_date` DESC';

	public static $ORDERS = array(self::ORDER_DATE_ASC, self::ORDER_DATE_DESC);

	public function __construct($name = null, $path = null) {
		$fullPath=null;
		if(!is_null($name) && !is_null($path)) {
			$fullPath = Directory::normalize($path);
		}

        $table = new DBTable();
        $table->column('id')->string(40)->primary();
        $table->column('filename')->string(355)->index();
        $table->column('original_filename')->string(355)->index();
        $table->column('path')->string(355)->index();
        $table->column('type')->string(255)->index();
        $table->column('bytes')->integer()->index();
        $table->column('created_date')->datetime()->index();

		parent::__construct($table);

        $this->id = Guid::create();
        $this->filename = $name;
        $this->path = $path;

        if($fullPath && is_file($fullPath)) {
            $this->type = File::getMime($fullPath);
            $this->bytes = filesize($fullPath);
        }

        $this->created_date = Date::toDateTime();
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

	public function setCreatedDate($datetime) {
		$this->created_date = $datetime;
	}

	public function updateData() {
		if($this->data) {
			/* Remove all fields */
			FileData::removeAll($this->id);
			foreach($this->data->getData() as $key=>$value) {
				$data=new FileData($this->id, $key, $value);
				$data->save();
			}
		}
	}

	protected function fetchData() {
		$data = FileData::getByFileId($this->id);
		if($data->hasRows()) {
			foreach($data->getRows() as $d) {
				$this->setDataValue($d->key, $d->value);
			}
		}
	}

	public function getFullPath() {
		return $this->path . Directory::normalize($this->path) . $this->Filename;
	}

	/**
	 * Get file by file id.
	 * @param string $id
	 * @return self
	 */
	public static function getById($id){
		return self::fetchOne('SELECT * FROM {table} WHERE `id` = %s', array($id));
	}

	public static function get($order=null, $rows=null, $page=null){
		$order = (in_array($order, self::$ORDERS)) ? $order : self::ORDER_DATE_DESC;
		return self::fetchPage('SELECT f.* FROM {table} f ORDER BY ' .$order, $rows=null,$page=null);
	}
}