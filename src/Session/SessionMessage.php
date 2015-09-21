<?php
namespace Pecee\Session;
use Pecee\UI\Form\FormMessage;

class SessionMessage {
	protected $session;
	protected $messages;
	protected static $instance;
	const KEY='MSG';
	
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new static();
		}
		return self::$instance;
	}
	
	public function __construct() {
		$this->parseMessages();
	}
	
	protected function parseMessages() {
		$this->messages= Session::getInstance()->get(self::KEY);
	}
	
	protected function saveMessages() {
		Session::getInstance()->set(self::KEY, $this->messages);
	}
	
	public function set(FormMessage $message, $type = null) {
		// Ensure no double posting
		if(isset($this->messages[$type]) && is_array($this->messages[$type])) {
			if(!in_array($message, $this->messages[$type])) {
				$this->messages[$type][] = $message;
				$this->saveMessages();
			}
		} else {
			$this->messages[$type][] = $message;
			$this->saveMessages();
		}
	}
	
	/**
	 * Get messages
	 * @param string|null $type
	 * @param string|null $default
	 * @return \Pecee\UI\Form\FormMessage|null
	 */
	public function get($type = null, $default = null) {
		if(!is_null($type)) {
			return (isset($this->messages[$type])) ? $this->messages[$type] : $default;
		}
		return $this->messages;
	}
	
	/**
	 * Checks if there's any messages
	 * @param string|null $type
	 * @return boolean
	 */
	public function hasMessages($type = null) {
		if(!is_null($type)) {
			return (isset($this->messages[$type]) && count($this->messages[$type]) > 0);
		}
		return (count($this->messages) > 0);
	}
	
	public function clear($type = null) {
		if(!is_null($type)) {
			unset($this->messages[$type]);
			$this->saveMessages();
		} else {
			Session::getInstance()->destroy(self::KEY);
		}
	}
}