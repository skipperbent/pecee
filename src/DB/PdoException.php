<?php
namespace Pecee\DB;
class PdoException extends \Exception {

	protected $query;

	public function __construct($text, $code = 0, $query = null) {
		parent::__construct($text, $code);
		$this->query=$query;
	}

	public function getQuery() {
		return $this->query;
	}

}