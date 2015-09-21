<?php
namespace Pecee;
abstract class Dataset {
	protected $data = array();

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	public function get($index) {
		for($i = 0; $i < count($this->data); $i++) {
			if($i == $index) {
				return $this->data[$i];
			}
		}

		return null;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data) {
		$this->data = $data;
	}

	protected function add($value=null, $name) {
		$arr = array();
		if(!is_null($value)) {
			$arr['value'] = htmlspecialchars($value);
		}
		$arr['name'] = $name;
		$this->data[] = $arr;
		return $this;
	}
}