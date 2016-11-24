<?php
namespace Pecee\Session;

use Pecee\UI\Form\FormMessage;

class SessionMessage
{
	const KEY = 'MSG';

	protected $messages;

	public function __construct()
	{
		$this->parse();
	}

	protected function parse()
	{
		$this->messages = Session::get(self::KEY);
	}

	public function save()
	{
		Session::set(self::KEY, $this->messages);
	}

	public function set(FormMessage $message, $type = null)
	{
		// Ensure no double posting
		if (isset($this->messages[$type]) && is_array($this->messages[$type])) {
			if (!in_array($message, $this->messages[$type])) {
				$this->messages[$type][] = $message;
				$this->save();
			}
		} else {
			$this->messages[$type][] = $message;
			$this->save();
		}
	}

	/**
	 * Get messages
	 * @param string|null $type
	 * @param object|null $default
	 * @return \Pecee\UI\Form\FormMessage|object
	 */
	public function get($type = null, $default = null)
	{
		if ($type !== null) {
			return (isset($this->messages[$type])) ? $this->messages[$type] : $default;
		}

		return $this->messages;
	}

	/**
	 * Checks if there's any messages
	 * @param string|null $type
	 * @return boolean
	 */
	public function has($type = null)
	{
		if ($type !== null) {
			return (isset($this->messages[$type]) && count($this->messages[$type]) > 0);
		}

		return (count($this->messages) > 0);
	}

	public function clear($type = null)
	{
		if ($type !== null) {
			unset($this->messages[$type]);
			$this->save();
		} else {
			Session::destroy(self::KEY);
		}
	}
}