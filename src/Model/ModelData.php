<?php
namespace Pecee\Model;
use Pecee\DB\DBTable;
use Pecee\Collection;

abstract class ModelData extends Model {
	public $data;
	public function __construct(DBTable $table) {
		parent::__construct($table);
		$this->data = new Collection();
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
					if($row instanceof Model) {
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

	public function setData(array $data) {
		$keys=array_map('strtolower', array_keys($this->getRows()));
		foreach($data as $key=>$d) {
			if(!in_array(strtolower($key), $keys)) {
				$this->data->$key=$d;
			}
		}
	}
}