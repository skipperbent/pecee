<?php
namespace Pecee\Model;

use Pecee\Collection\CollectionItem;

abstract class ModelData extends Model {

	public $data;

	protected $dataKeyField = 'key';
	protected $dataValueField = 'value';

	public function __construct() {
		parent::__construct();
		$this->data = new CollectionItem();
	}

	abstract protected function getDataClass();

	abstract protected function createNewDataItem($key, $value);

	abstract protected function fetchData();

	protected function updateData() {

		if($this->data !== null) {

			$currentFields = $this->fetchData();

			$cf = array();
			foreach($currentFields as $field) {
				$cf[strtolower($field->{$this->dataKeyField})] = $field;
			}

			if(count($this->data->getData())) {

				foreach($this->data->getData() as $key => $value) {

					if($value === null) {
						continue;
					}

					if(isset($cf[strtolower($key)])) {
						if($cf[$key]->value === $value) {
							unset($cf[$key]);
							continue;
						} else {
							$cf[$key]->{$this->dataKeyField} = $key;
                            $cf[$key]->{$this->dataValueField} = $value;
							$cf[$key]->save();
							unset($cf[$key]);
						}
					} else {
						$field = $this->createNewDataItem($key, $value);
						$field->save();
					}
				}
			}

			foreach($cf as $field) {
				$field->delete();
			}
		}
	}

	public function save() {
		parent::save();
		$this->updateData();
	}

	public function onInstanceCreate() {
		$data = $this->fetchData();
		if($data->hasRows()) {
			foreach($data->getRows() as $d) {
				$this->data->{$d->{$this->dataKeyField}} = $d->{$this->dataValueField};
			}
		}
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