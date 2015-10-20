<?php
namespace Pecee\DB;
class PdoException extends \Exception {

	protected $query;

	public function __construct($text, $code = 0, $query = null) {
		//$this->code = $code;
		die(var_dump($text));
		parent::__construct($text, 0);
		$this->query=$query;
	}

	public function getQuery() {
		return $this->query;
	}

}