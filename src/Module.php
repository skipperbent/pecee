<?php
namespace Pecee;
class Module {
	protected static $instance;
	protected $modules;
	/**
	 * Get instance
	 * @return static
	 */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->modules = array();
	}

	/**
	 * Add new module
	 * @param string $name
	 * @param string $path
	 */
	public function add($name, $path) {
		$this->modules[$name] = $path;
	}

	/**
	 * Get module
	 * @param string $name
	 * @return string
	 */
	public function get($name) {
		return (isset($this->modules[$name]) ? $this->modules[$name] : null);
	}

	/**
	 * Get modules
	 * @return array
	 */
	public function getModules() {
		return $this->modules;
	}
}