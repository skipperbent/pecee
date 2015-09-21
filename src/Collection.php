<?php
namespace Pecee;
class Collection {
	protected $data;

	public function __construct(array $rows = null) {
		if(!is_null($rows)) {
			$this->setData($rows);
		}
	}

	public function setData(array $arr) {
		$this->data=array();
		foreach($arr as $i=>$row) {
			$this->__set(strtolower($i), $row);
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

	public function __call($name, $args) {
		if(strlen($name) > 3) {
			switch(substr(strtolower($name), 0, 3)) {
				case 'get':
					return $this->__get(substr($name, 3));
					break;
				case 'set':
					$this->__set(substr($name, 3), $args[0]);
					return null;
					break;
			}
		}

		throw new \InvalidArgumentException('The field "'.$name.'" couldt not be found');
	}

	public function getData() {
		return $this->data;
	}

}