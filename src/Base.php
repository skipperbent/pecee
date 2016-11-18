<?php
namespace Pecee;

use Pecee\Http\Input\InputItem;
use Pecee\Session\Session;
use Pecee\Session\SessionMessage;
use Pecee\UI\Form\FormMessage;

abstract class Base {

    protected $errorType = 'danger';
    protected $defaultMessagePlacement = 'default';
    protected $_inputSessionKey = 'InputValues';
    protected $_messages;
    protected $_validations = array();

    public function __construct() {
        debug('BASE CLASS ' . static::class);
        $this->_messages = new SessionMessage();
        $this->setInputValues();
    }

    protected function setInputValues() {
        if(Session::exists($this->_inputSessionKey)) {
            $values = Session::get($this->_inputSessionKey);

            foreach($values as $key => $value) {
                $item = input()->getObject($key, new InputItem($key))->setValue($value);
                if(request()->getMethod() === 'post') {
                    input()->post->$key = $item;
                } else {
                    input()->get->$key = $item;
                }
            }

            Session::destroy($this->_inputSessionKey);
        }
    }

    public function setInputName(array $names) {
        foreach($names as $key => $name) {
            $item = input()->getObject($key);

            /* @var $item InputItem */
            if($item !== null) {
                $item->setName($name);
            }
        }
    }

    public function saveInputValues(array $values = null) {

        if($values === null) {
            $values = input()->all();
        }

        Session::set($this->_inputSessionKey, $values);
    }

    protected function validate(array $validation = null) {
        if($validation !== null) {
            foreach ($validation as $key => $validations) {

                if (!is_array($validations)) {
                    $validations = array($validations);
                }

                $this->_validations[$key] = $validations;
            }
        }
    }

    protected function validateInput() {
        foreach($this->_validations as $key => $validations) {
            /* @var $input \Pecee\Http\Input\InputItem */
            /* @var $i \Pecee\Http\Input\InputItem */
            $input = input()->getObject($key, new InputItem($key, null));

            /* @var $validation \Pecee\UI\Form\Validation\ValidateInput */
            foreach($validations as $validation) {

                if(is_array($input)) {
                    foreach($input as $i) {
                        if($validation === '') {
                            continue;
                        }
                        $validation->setInput($i);
                        if(!$validation->validates()) {
                            $this->setMessage($validation->getError(), $this->errorType, $validation->getPlacement(), $i->getIndex());
                        }
                    }
                } else {
                    if($validation === '') {
                        continue;
                    }
                    $validation->setInput($input);
                    if(!$validation->validates()) {
                        $this->setMessage($validation->getError(), $this->errorType, $validation->getPlacement(), $input->getIndex());
                    }
                }

            }

        }
    }

    public function isAjaxRequest() {
        return (request()->getHeader('http_x_requested_with') !== null && strtolower(request()->getHeader('http_x_requested_with')) === 'xmlhttprequest');
    }

    protected function appendSiteTitle($title, $separator = '-') {
        $separator = ($separator === null) ? '': sprintf(' %s ', $separator);
        request()->site->setTitle((request()->site->getTitle() . $separator . $title));
    }

    protected function prependSiteTitle($title, $separator = ' - ') {
        request()->site->setTitle(($title . $separator . request()->site->getTitle()));
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
     * @return \Pecee\UI\Site
     */
    public function getSite() {
        return request()->site;
    }

    /**
     * Get form message
     * @param string $type
     * @param string|null $placement
     * @return FormMessage|null
     */
    public function getMessage($type, $placement = null){
        $messages = $this->getMessages($type, $placement);
        if(count($messages)) {
            return $messages[0];
        }
        return null;
    }

    /**
     * Get form messages
     * @param string $type
     * @param string|null $placement
     * @return array
     */
    public function getMessages($type, $placement = null) {
        // Trigger validation
        $this->validateInput();

        $messages = array();

        if($this->_messages->get($type) !== null) {
            /* @var $message FormMessage */
            foreach ($this->_messages->get($type) as $message) {
                if (($placement === null || $message->getPlacement() === $placement)) {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }

    public function hasMessages($type, $placement = null) {
        return (count($this->getMessages($type, $placement)));
    }

    /**
     * Set message
     * @param string $message
     * @param string $type
     * @param string|null $placement Key to use if you want the message to be displayed an unique place
     * @param string|null $index
     */
    protected function setMessage($message, $type, $placement = null, $index = null) {

        $placement = ($placement === null) ? $this->defaultMessagePlacement : $placement;

        $msg = new FormMessage();
        $msg->setMessage($message);
        $msg->setPlacement($placement);
        $msg->setIndex($index);
        $this->_messages->set($msg, $type);
    }

    public function hasErrors($placement = null, $errorType = null) {
        $errorType = ($errorType === null) ? $this->errorType : $errorType;
        return $this->hasMessages($errorType, $placement);
    }

    /**
     * Set error
     * @param string $message
     * @param string|null $placement
     */
    protected function setError($message, $placement = null) {
        $this->setMessage($message, $this->errorType, $placement);
    }

    /**
     * Get error messages
     * @param string|null $placement
     * @param string|null $errorType
     * @return array
     */
    public function getErrors($placement = null, $errorType = null) {
        $errorType = ($errorType === null) ? $this->errorType : $errorType;
        return $this->getMessages($errorType, $placement);
    }

    public function getErrorsArray($placement = null) {
        $output = array();

        /* @var $error FormMessage */
        foreach($this->getMessages($this->errorType, $placement) as $error) {
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
                    $input = input()->post->findFirst($index);
                    if($input === null) {
                        $input = input()->file->findFirst($index);
                    }
                } else {
                    $input = input()->get->findFirst($index);
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