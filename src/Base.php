<?php

namespace Pecee;

use Pecee\Http\Input\InputItem;
use Pecee\Session\Session;
use Pecee\Session\SessionMessage;
use Pecee\UI\Form\FormMessage;
use Pecee\UI\Form\Validation\ValidateInput;

abstract class Base
{
    protected $errorType = 'danger';
    protected $defaultMessagePlacement = 'default';
    protected $_inputSessionKey = 'InputValues';
    protected $_messages;
    protected $_validations = [];

    public function __construct()
    {
        $this->_messages = new SessionMessage();
        $this->setInputValues();
    }

    protected function setInputValues()
    {
        if (Session::exists($this->_inputSessionKey) === true) {
            $values = Session::get($this->_inputSessionKey);

            /* @var array $values */
            foreach ($values as $key => $value) {
                $item = input()->getObject($key, new InputItem($key), ['get', 'post'])->setValue($value);
                if (request()->getMethod() === 'post') {
                    input()->post[$key] = $item;
                } else {
                    input()->get[$key] = $item;
                }
            }

            Session::destroy($this->_inputSessionKey);
        }
    }

    public function setInputName(array $names)
    {
        foreach ($names as $key => $name) {
            $item = input()->getObject($key);

            /* @var $item \Pecee\Http\Input\IInputItem */
            if ($item !== null) {
                $item->setName($name);
            }
        }
    }

    public function saveInputValues(array $values = null)
    {
        if ($values === null) {
            $values = input()->all();
        }

        Session::set($this->_inputSessionKey, $values);
    }

    protected function validate(array $validation = null)
    {
        if ($validation !== null) {
            foreach ($validation as $key => $validations) {
                if (is_array($validations) === false) {
                    $validations = [$validations];
                }

                $this->_validations[$key] = $validations;
            }
        }
    }

    protected function onInputError(InputItem $input, $error)
    {

    }

    protected function performValidation()
    {
        foreach ($this->_validations as $key => $validations) {

            $input = input()->getObject($key, new InputItem($key, null));

            for ($i = 0, $max = count($validations); $i < $max; $i++) {

                /* @var $validation ValidateInput */
                $validation = $validations[$i];

                if ($validation === null || $validation === '') {
                    continue;
                }

                $inputs = ($input instanceof InputItem) ? [$input] : $input;

                for ($x = 0, $xMax = count($inputs); $x < $xMax; $x++) {

                    $input = $inputs[$x];
                    $validation->setInput($input);

                    if ($validation->runValidation() === false) {
                        $this->setMessage($validation->getError(), $this->errorType, $validation->getPlacement(), $input->getIndex());
                        $this->onInputError($input, $validation->getError());
                    }
                }
            }
        }
    }

    public function isAjaxRequest()
    {
        return (request()->getHeader('http-x-requested-with') !== null && strtolower(request()->getHeader('http-x-requested-with')) === 'xmlhttprequest');
    }

    protected function appendSiteTitle($title, $separator = '-')
    {
        $separator = ($separator === null) ? '' : ' ' . $separator . ' ';
        app()->site->setTitle(app()->site->getTitle() . $separator . $title);
    }

    protected function prependSiteTitle($title, $separator = ' - ')
    {
        app()->site->setTitle($title . $separator . app()->site->getTitle());
    }

    /**
     * Checks if there has been a form post-back
     * @return bool
     */
    public function isPostBack()
    {
        return (request()->getMethod() !== 'get');
    }

    /**
     * Get site
     * @return \Pecee\UI\Site
     */
    public function getSite()
    {
        return app()->site;
    }

    /**
     * Get form message
     * @param string $type
     * @param string|null $placement
     * @return FormMessage|null
     */
    public function getMessage($type, $placement = null)
    {
        $messages = $this->getMessages($type, $placement);
        if (count($messages)) {
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
    public function getMessages($type, $placement = null)
    {
        // Trigger validation
        $this->performValidation();

        $messages = [];
        $search = $this->_messages->get($type);

        if ($search !== null) {
            /* @var $search array */
            /* @var $message FormMessage */
            foreach ($search as $message) {
                if ($placement === null || $message->getPlacement() === $placement) {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }

    public function hasMessages($type, $placement = null)
    {
        return (bool)count($this->getMessages($type, $placement));
    }

    /**
     * Set message
     * @param string $message
     * @param string $type
     * @param string|null $placement Key to use if you want the message to be displayed an unique place
     * @param string|null $index
     */
    protected function setMessage($message, $type, $placement = null, $index = null)
    {
        $msg = new FormMessage();
        $msg->setMessage($message);
        $msg->setPlacement(($placement === null) ? $this->defaultMessagePlacement : $placement);
        $msg->setIndex($index);
        $this->_messages->set($msg, $type);
    }

    public function hasErrors($placement = null, $errorType = null)
    {
        return $this->hasMessages(($errorType === null) ? $this->errorType : $errorType, $placement);
    }

    /**
     * Set error
     * @param string $message
     * @param string|null $placement
     */
    protected function setError($message, $placement = null)
    {
        $this->setMessage($message, $this->errorType, $placement);
    }

    /**
     * Get error messages
     * @param string|null $placement
     * @param string|null $errorType
     * @return array
     */
    public function getErrors($placement = null, $errorType = null)
    {
        return $this->getMessages(($errorType === null) ? $this->errorType : $errorType, $placement);
    }

    public function getErrorsArray($placement = null)
    {
        $output = [];

        /* @var $error FormMessage */
        foreach ($this->getMessages($this->errorType, $placement) as $error) {
            $output[] = $error->getMessage();
        }

        return $output;
    }

    public function getValidation($index)
    {
        $messages = [];
        $search = $this->_messages->get($this->errorType);

        if ($search !== null) {
            /* @var $message FormMessage */
            foreach ($search as $message) {
                if ($message->getIndex() === $index) {
                    return $message->getMessage();
                }
            }
        }

        return $messages;
    }

}