<?php
namespace Pecee;

use Pecee\Session\SessionMessage;
use Pecee\UI\Form\FormMessage;
use Pecee\UI\ResponseData\ResponseDataFile;
use Pecee\UI\ResponseData\ResponseDataGet;
use Pecee\UI\ResponseData\ResponseDataPost;
use Pecee\UI\Site;

abstract class Base {

	const MSG_ERROR='error';

	protected $_site;
	protected $data;
	protected $request;
	protected $files;
	protected $_messages;

	public function __construct() {

		Debug::getInstance()->add('BASE CLASS ' . get_class($this));

		$this->_site = Site::getInstance();
		$this->_messages = SessionMessage::getInstance();
		$this->data = new ResponseDataPost();
		$this->request = new ResponseDataGet();
		$this->files = new ResponseDataFile();
	}

	public function isAjaxRequest() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

	/**
	 * Get form message
	 * @param string $type
	 * @return FormMessage|null
	 */
	public function getMessage($type){
		$errors = $this->getMessages($type);
		if($errors && is_array($errors)) {
			return $errors[0];
		}
		return null;
	}

	/**
	 * Get form messages
	 * @param string $type
	 * @return FormMessage|null
	 */
	public function getMessages($type) {
		return $this->_messages->get($type);
	}

	public function hasMessages($type) {
		return $this->_messages->hasMessages($type);
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
		return $this->showMessages(self::MSG_ERROR, $formName);
	}

	public function hasErrors() {
		return $this->hasMessages(self::MSG_ERROR);
	}

	/**
	 * Set error
	 * @param string $message
	 */
	protected function setError($message) {
		$this->setMessage($message, self::MSG_ERROR);
	}

	/**
	 * Get error messages
	 * @return array
	 */
	public function getErrors() {
		return $this->getMessages(self::MSG_ERROR);
	}

	public function getErrorsArray() {
		$output = array();

		/* @var $error FormMessage */
		foreach($this->getMessages(self::MSG_ERROR) as $error) {
			$output[] = $error->getMessage();
		}

		return $output;
	}

	public function getFormName($post=true) {
		if($this->isPostBack() && $post) {
			return ResponseDataPost::GetFormName();
		}
		return ResponseDataGet::GetFormName();
	}

	protected function appendSiteTitle($title, $seperator='-') {
		$seperator=is_null($seperator) ? '': sprintf(' %s ', $seperator);
		$this->_site->setTitle(($this->_site->getTitle() . $seperator . $title));
	}

	protected function prependSiteTitle($title, $seperator=' - ') {
		$this->_site->setTitle(($title . $seperator .$this->_site->getTitle()));
	}

	/**
	 * Adds input validation
	 *
	 * @param string $name
	 * @param string $index
	 * @param \Pecee\UI\Form\Validate\ValidateInput|array $type
	 */
	protected function addInputValidation($name, $index, $type) {
		if(Util::getTypeOf($type) == 'Pecee\\UI\\Form\\Validate\\ValidateFile') {
			$this->files->addInputValidation($name, $index, $type);
		} else {
			$this->data->addInputValidation($name, $index, $type);
		}
	}

	/**
	 * Get request
	 * @param string $formName
	 * @param string $elementName
	 * @return ResponseDataGet
	 */
	public function request($elementName, $formName = null) {
		$element = $this->request->__get( (($formName) ? $formName . '_' : null) . $elementName);
		return ($element) ? $element : null;
	}

	/**
	 * Checks if there has been a form post-back
	 * @return bool
	 */
	public function isPostBack() {
		return ResponseDataPost::IsPostBack();
	}

	protected function getKey($name) {
		$key=$this->getFormName(false);
		if(isset($_GET[$key.'_'.$name])) {
			return $key.'_'.$name;
		}
		if(isset($_GET[$name])) {
			return $name;
		}
		return null;
	}

	/**
	 * Check if a certain param has been set
	 * @param string $name
	 * @return bool
	 */
	public function hasParam($name) {
		return isset($_GET[$this->getKey($name)]);
	}

	/**
	 * Get param
	 * @param string $name
	 * @param string|null $default
	 * @return string|null
	 */
	public function getParam($name, $default=null) {
		return ($this->hasParam($name) ? Str::getFirstOrDefault($_GET[$this->getKey($name)],$default) : $default);
	}

	/**
	 * Get site
	 * @return Site
	 */
	public function getSite() {
		return $this->_site;
	}

	public function __destruct() {
		$this->_messages->clear();
	}

}