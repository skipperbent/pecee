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

	protected function fetchData($row) {
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

	protected function setEntityFields($single=false) {
		if($single && $this->hasRow()) {
			$this->fetchData($this);
		} else {
			if($this->hasRows()) {
				foreach($this->getRows() as $row) {
					$this->fetchData($row);
				}
			}
		}
	}

	protected function setDataValue($name, $value) {
		$this->data->$name=$value;
	}

	public static function FetchAll($query, $args = null) {
		$args = (is_null($args) || is_array($args) ? $args : \Pecee\DB\DB::ParseArgs(func_get_args(), 1));
		$model = parent::FetchAll($query, $args);
		$model->setEntityFields();
		return $model;
	}

	public static function FetchPage($query, $rows = 10, $page = 0, $args=null) {
		$args = (!$args || is_array($args) ? $args : \Pecee\DB\DB::ParseArgs(func_get_args(), 3));
		$model = parent::FetchPage($query, $rows, $page, $args);
		$model->setEntityFields();
		return $model;
	}

	public static function FetchRows($query, $startIndex=0, $rows = 10, $args = null) {
		$args = (!$args || is_array($args) ? $args : \Pecee\DB\DB::ParseArgs(func_get_args(), 3));
		$model = parent::FetchAll($query, $startIndex, $rows, $args);
		$model->setEntityFields();
		return $model;
	}

	public static function FetchOne($query, $args=null) {
		$args = (!$args || is_array($args) ? $args : \Pecee\DB\DB::ParseArgs(func_get_args(), 1));
		$model = parent::FetchOne($query, $args);
		$model->setEntityFields(true);
		return $model;
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
						$rows[$key]=(\Pecee\Integer::isInteger($row)) ? intval($row) : $row;
					}
				}
			}
			$data=$this->data->getData();
			$out=array();
			foreach($data as $key=>$d) {
				$out[$key]=(\Pecee\Integer::isInteger($d)) ? intval($d) : $d;
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