<?php
namespace Pecee\UI\Form;

class FormMessage {

	protected $name;
	protected $index;
	protected $message;
	protected $placement;

	/**
	 * Get name assosiated with the element (if any)
	 * @return string $name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get the index (if any)
	 * @return string $index
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Get message
	 * @return string $message
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Get name (if any)
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Set index
	 * @param string $index
	 */
	public function setIndex($index) {
		$this->index = $index;
	}

	/**
	 * Set message
	 * @param string $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	}

	/**
	 * @return string $placement
	 */
	public function getPlacement() {
		return $this->placement;
	}

	/**
	 * @param string $placement
	 */
	public function setPlacement($placement) {
		$this->placement = $placement;
	}

	/**
	 * @param string $form
	 */
	public function setForm($form) {
		$this->form = $form;
	}
}