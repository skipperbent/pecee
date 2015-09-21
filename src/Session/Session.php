<?php
namespace Pecee\Session;
class Session {
	protected static $instance;

	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function __construct() {
		session_start();
	}

 	public function isActive() {
		return (session_id());
	}

	public function destroy($id) {
		if($this->exists($id)) {
			unset($_SESSION[$id]);
			return true;
		}
		return false;
	}

	public function exists($id) {
		return (isset($_SESSION[$id]));
	}

	public function set($id, $value) {
		$_SESSION[$id] = $value;
	}

	public function get($id, $default = null) {
		return ($this->exists($id)) ? $_SESSION[$id] : $default;
	}
}