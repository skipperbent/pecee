<?php
namespace Pecee\UI\ResponseData;

use Pecee\Session\SessionMessage;
use Pecee\UI\Form\FormMessage;
use Pecee\UI\Form\Validate\ValidateInput;
use Pecee\Widget;

abstract class ResponseData {

	protected $data;

	public function __construct() {
		$this->data = array();
	}

	/**
	 * Adds validation input
	 * @param \Pecee\UI\Form\Validate\ValidateInput|array $type
	 */
	protected function validateInput($type) {
		if(is_array($type)) {
			foreach($type as $t) {
				if(!$t->validate()) {
					$this->addError($t);
					break;
				}
			}
		} else {
			if(!$type->validate())
				$this->addError($type);
		}
	}

	protected function addError(ValidateInput $type) {
		$obj=new FormMessage();
		$obj->setIndex($type->getIndex());
		$obj->setMessage($type->getErrorMessage());
		$obj->setName($type->getName());
		$obj->setForm($type->getForm());

		$msg=SessionMessage::getInstance();
		$msg->set($obj, Widget::MSG_ERROR);
	}

	public function getArray() {
		$out=array();
		foreach($this->data as $key=>$p) {
			$n = explode('_', $key);
			$out[(isset($n[1])) ? $n[1] : $key]=$p;
		}
		return $out;
	}

	public function __get($name) {
		if(isset($this->data[strtolower($name)])) {
			return $this->data[strtolower($name)];
		} else {
			foreach($this->data as $key=>$p) {
				$n = explode('_', $key);
				if(isset($n[1]) && strtolower($n[1]) == strtolower($name))
					return $this->data[$key];
			}
		}
		return null;
	}

	public function __set($name, $value) {
		$this->data[strtolower($name)] = $value;
	}

	/**
	 * @param string $name
     * @param array|null $args
     * @return string
     * @throws \InvalidArgumentException
	 */
	public function __call($name, $args=null) {
		if(strlen($name) > 3) {
			switch(strtolower(substr($name, 0, 3))) {
				case 'get':
					return $this->__get(substr(strtolower($name), 3, strlen($name)));
					break;
			}
		}
		throw new \InvalidArgumentException('Function not reconized.');
	}
}