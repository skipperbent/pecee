<?php
namespace Pecee;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\Input;
use Pecee\Session\SessionMessage;
use Pecee\UI\Form\FormMessage;
use Pecee\UI\Site;

abstract class Base {

	protected $errorType = 'danger';
	protected $_messages;
	protected $_site;
	protected $_input;
	protected $get;
	protected $post;
	protected $file;

	public function __construct() {

		Debug::getInstance()->add('BASE CLASS ' . get_class($this));

		$this->_site = Site::getInstance();
		$this->_input = new Input();

		// Add shortcuts
		$this->get = $this->_input->get;
		$this->post = $this->_input->post;
		$this->file = $this->_input->file;

		$this->_messages = SessionMessage::getInstance();
	}

	protected function validateInput() {
		// Validate inputs

		/* @var $item \Pecee\Http\Input\InputItem */
		foreach($this->get as $item) {
			if($item instanceof IInputItem && !$item->validates()) {
				/* @var $error \Pecee\Http\Input\Validation\ValidateInput */
				foreach($item->getValidationErrors() as $error) {
					$this->setMessage($error->getErrorMessage(), $this->errorType, $error->getForm(), null, $error->getIndex());
				}
			}
		}

		if(request()->getMethod() !== 'get') {

			foreach($this->post as $item) {
				if($item instanceof IInputItem && !$item->validates()) {
					/* @var $error \Pecee\Http\Input\Validation\ValidateInput */
					foreach ($item->getValidationErrors() as $error) {
						$this->setMessage($error->getErrorMessage(), $this->errorType, $error->getForm(), null, $error->getIndex());
					}
				}
			}

			foreach($this->file as $item) {
				if($item instanceof IInputItem && !$item->validates()) {
					/* @var $error \Pecee\Http\Input\Validation\ValidateInput */
					foreach($item->getValidationErrors() as $error) {
						$this->setMessage($error->getErrorMessage(), $this->errorType, $error->getForm(), null, $error->getIndex());
					}
				}
			}
		}
	}

	public function isAjaxRequest() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

	protected function appendSiteTitle($title, $separator = '-') {
		$separator = ($separator === null) ? '': sprintf(' %s ', $separator);
		$this->_site->setTitle(($this->_site->getTitle() . $separator . $title));
	}

	protected function prependSiteTitle($title, $separator = ' - ') {
		$this->_site->setTitle(($title . $separator .$this->_site->getTitle()));
	}

	/**
	 * Get input element value matching index
	 * @param string $index
	 * @param string|null $default
	 * @return string|null
	 */
	public function input($index, $default = null) {
		$element = $this->get->findFirst($index);

		if($element !== null) {

			if(is_array($element->getValue())) {
				return $element->getValue();
			}

			return Str::getFirstOrDefault($element->getValue(), $default);
		}

		$element = $this->post->findFirst($index);

		if($element !== null) {

			if(is_array($element->getValue())) {
				return $element->getValue();
			}

			return Str::getFirstOrDefault($element->getValue(), $default);
		}

		$element = $this->file->findFirst($index);

		if($element !== null) {
			return $element;
		}

		return $default;
	}

	/**
	 * Get post input element
	 * @param string $index
	 * @return \Pecee\Http\Input\InputFile|null
	 */
	public function file($index) {
		$element = $this->file->findFirst($index);
		return ($element !== null) ? $element : null;
	}

	/**
	 * Checks if there has been a form post-back
	 * @return bool
	 */
	public function isPostBack() {
		return (request()->getMethod() !== 'get');
	}

	/**
	 * Get site
	 * @return Site
	 */
	public function getSite() {
		return $this->_site;
	}

	/**
	 * Get form message
	 * @param string $type
	 * @param string $form
	 * @return FormMessage|null
	 */
	public function getMessage($type, $form = null){
		$errors = $this->getMessages($type);
		if($errors && is_array($errors)) {
			if($form === null || $errors[0]->getForm() === $form) {
				return $errors[0];
			}
		}
		return null;
	}

	/**
	 * Get form messages
	 * @param string $type
	 * @param string $form
	 * @return FormMessage|null
	 */
	public function getMessages($type, $form = null) {
		// Trigger validation
		$this->validateInput();

		$messages = array();

		if($this->_messages->get($type) !== null) {
			foreach ($this->_messages->get($type) as $message) {
				if ($form === null || $message->getForm() === $form) {
					$messages[] = $message;
				}
			}
		}

		return $messages;
	}

	public function hasMessages($type, $form = null) {
		return (count($this->getMessages($type, $form)) > 0);
	}

	/**
	 * Set message
	 * @param string $message
	 * @param string $type
	 * @param string|null $form
	 * @param string|null $placement Key to use if you want the message to be displayed an unique place
	 * @param string|null $index
	 */
	protected function setMessage($message, $type, $form=null, $placement=null, $index = null) {
		$msg = new FormMessage();
		$msg->setForm($form);
		$msg->setMessage($message);
		$msg->setPlacement($placement);
		$msg->setIndex($index);
		$this->_messages->set($msg, $type);
	}

	public function showErrors($formName=null) {
		return $this->showMessages($this->errorType, $formName);
	}

	public function hasErrors() {
		return $this->hasMessages($this->errorType);
	}

	/**
	 * Set error
	 * @param string $message
	 * @param string|null $form
	 */
	protected function setError($message, $form = null) {
		$this->setMessage($message, $this->errorType, $form);
	}

	/**
	 * Get error messages
	 * @return array
	 */
	public function getErrors() {
		return $this->getMessages($this->errorType);
	}

	public function getErrorsArray($form = null) {
		$output = array();

		/* @var $error FormMessage */
		foreach($this->getMessages($this->errorType, $form) as $error) {
			$output[] = $error->getMessage();
		}

		return $output;
	}

	public function validationFor($index) {
		$messages = $this->_messages->get($this->errorType);
		if($messages && is_array($messages)) {
			/* @var $message \Pecee\UI\Form\FormMessage */
			foreach($messages as $message) {

				$input = null;
				if(request()->getMethod() !== 'get') {
					$input = $this->post->findFirst($index);
					if($input === null) {
						$input = $this->file->findFirst($index);
					}
				} else {
					$input = $this->get->findFirst($index);
				}

				if($input !== null) {
					if ($message->getIndex() === $input->getIndex()) {
						return $message->getMessage();
					}
				}
			}
		}
		return null;
	}

}