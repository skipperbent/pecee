<?php
namespace Pecee;
class Module {
	protected static $instance;
	protected $modules;
	/**
	 * Get instance
	 * @return self
	 */
	public static function getInstance() {
		if(!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->modules = array();
	}

	/**
	 * Add new module
	 * @param string $appname
	 * @param string $path
	 */
	public function add($appname, $path) {
		$this->modules[$appname] = $path;
	}

	/**
	 * Get module
	 * @param string $appname
	 * @return string
	 */
	public function get($appname) {
		return (isset($this->modules[$appname]) ? $this->modules[$appname] : null);
	}

	/**
	 * Get modules
	 * @return array
	 */
	public function getModules() {
		return $this->modules;
	}
}