<?php
namespace Pecee\Dataset;

abstract class Dataset implements \IteratorAggregate {

	protected $data = array();

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	public function get($index) {
		foreach($this->data as $data) {
			if($data['value'] === $index) {
				return $data['name'];
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
		if($value !== null) {
			$arr['value'] = htmlspecialchars($value);
		}
		$arr['name'] = $name;
		$this->data[] = $arr;
		return $this;
	}

	/**
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator() {
		return new \ArrayIterator($this->data);
	}

}