<?php
namespace Pecee;

class Modules {

	protected $modules = array();

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
		return isset($this->modules[$name]) ? $this->modules[$name] : null;
	}

	/**
	 * Get modules
	 * @return array
	 */
	public function getList() {
		return $this->modules;
	}

	public function hasModules() {
		return (count($this->modules) > 0);
	}
}