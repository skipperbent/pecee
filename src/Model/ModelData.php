<?php
namespace Pecee\Model;
use Pecee\DB\DBTable;
use Pecee\Collection;

abstract class ModelData extends \Pecee\Model\Model {
	public $data;
	public function __construct(DBTable $table) {
		parent::__construct($table);
		$this->data=new Collection();
	}

	protected function updateData() {
		// Implement this method in your extending class
		throw new \ErrorException('Yet not implemented');
	}

	protected function fetchData() {
		// Implement this method in your extending class
		throw new \ErrorException('Yet not implemented');
	}

	public function update() {
		$this->updateData();
		return parent::update();
	}

	public function save() {
		parent::save();
		$this->updateData();
	}

    protected function setDataValue($name, $value) {
		$this->data->$name=$value;
	}

    public function setRows(array $rows) {
        parent::setRows($rows);
        $this->fetchData();
    }

	public function getAsJsonObject(){
		$arr=array('rows' => null);
		$arr=array_merge($arr, (array)$this->results['data']);
		if($this->hasRow()){
			$rows=$this->results['data']['rows'];
			if($rows && is_array($rows)) {
				foreach($rows as $key=>$row){
					if($row instanceof \Pecee\Model\Model) {
						$rows[$key]=$this->parseJsonRow($row);
					} else {
						$row=$this->parseJsonRow($row);
						$rows[$key]=(\Pecee\PhpInteger::isInteger($row)) ? intval($row) : $row;
					}
				}
			}
			$data=$this->data->getData();
			$out=array();
			foreach($data as $key=>$d) {
				$out[$key]=(\Pecee\PhpInteger::isInteger($d)) ? intval($d) : $d;
			}
			$rows=array_merge($rows, $out);
			if(count($this->getResults()) == 1) {
				return $rows;
			}
			$arr['rows']=$rows;
		}
		return $arr;
	}

	public function __call($name, $args=null) {
		if(!method_exists($this, $name)){
			$index = substr($name, 3, strlen($name));
			switch(strtolower(substr($name, 0, 3))){
				case 'get':
					if(!is_null($this->data->__get($index))) {
						return $this->data->__get($index);
					} else {
                        return $this->__get($index);
					}
					break;
				case 'set':
					$this->__set($index, $args[0]);
					return null;
					break;
			}
			$debug=debug_backtrace();
			throw new ModelException(sprintf('Unknown method: %s in %s on line %s', $name, $debug[0]['file'], $debug[0]['line']));
		}
        return call_user_func_array($name, $args);
	}

	public function setData(array $data) {
		$keys=array_map('strtolower', array_keys($this->getRows()));
		foreach($data as $key=>$d) {
			if(!in_array(strtolower($key), $keys)) {
				$this->data->$key=$d;
			}
		}
	}
}