<?php
namespace Pecee;
class Collection {
	protected $data;

	public function __construct(array $rows = null) {
		if($rows !== null) {
			$this->setData($rows);
		}
	}

	public function setData(array $arr) {
		$this->data = array();
		foreach($arr as $key => $value) {
			$this->__set($key, $value);
		}
	}

	public function get($name) {
		return $this->__get($name);
	}

	public function set($name, $value) {
		$this->__set($name, $value);
	}

	public function __get($name) {
		return isset($this->data[strtolower($name)]) ? $this->data[strtolower($name)] : null;
	}

	public function __set($name, $value) {
		$this->data[strtolower($name)] = $value;
	}

	public function getData() {
		return $this->data;
	}

	public function add($key, $value) {
		$this->data[$key] = $value;
	}

}