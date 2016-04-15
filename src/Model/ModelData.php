<?php
namespace Pecee\Model;

use Pecee\Collection\CollectionItem;

abstract class ModelData extends Model {

	public $data;

	public function __construct() {
		parent::__construct();
		$this->data = new CollectionItem();
	}

	abstract protected function updateData();

	abstract protected function fetchData();

	public function save() {
		parent::save();
		$this->updateData();
	}

	protected function setDataValue($name, $value) {
		$this->data->$name = $value;
	}

	public function onInstanceCreate() {
        $this->fetchData();
	}

	public function setData(array $data) {
		$keys = array_map('strtolower', array_keys($this->getRows()));
		foreach($data as $key => $d) {
			if(!in_array(strtolower($key), $keys)) {
				$this->data->$key = $d;
			}
		}
	}

	public function toArray() {
		$output = parent::toArray();
		if(is_array($output)) {
			return array_merge($this->data->getData(), $output);
		}
		return $output;
	}

}