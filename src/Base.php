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

    /**
     * @param array|null $values
     * @throws \RuntimeException
     */
    public function saveInputValues(array $values = null)
    {
        Session::set($this->_inputSessionKey, $values ?? input()->all());
    }

    protected function validate(array $validation)
    {
        $this->performValidation($validation);
    }

    protected function onInputError(InputItem $input, $error)
    {

    }

    protected function performValidation(array $validation)
    {
        foreach ($validation as $key => $validations) {

            $input = input()->getObject($key, new InputItem($key, null));
            $inputs = ($input instanceof InputItem) ? [$input] : $input;

            /* @var $validateClass ValidateInput */
            foreach ((array)$validations as $validateClass) {

                foreach ($inputs as $input) {
                    $validateClass->setInput($input);
                    if ($validateClass->runValidation() === false) {
                        $this->setMessage($validateClass->getError(), $this->errorType, $validateClass->getPlacement(), $input->getIndex());
                        $this->onInputError($input, $validateClass->getError());
                    }
                }

            }
        }
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
     * @throws \RuntimeException
     */
    public function getMessage($type, $placement = null)
    {
        $messages = $this->getMessages($type, $placement);

        return (\count($messages) > 0) ? $messages[0] : null;
    }

    /**
     * Get form messages
     * @param string $type
     * @param string|null $placement
     * @return array
     * @throws \RuntimeException
     */
    public function getMessages($type, $placement = null)
    {
        // Trigger validation
        $this->performValidation();

        $messages = [];
        $search = $this->_messages->get($type);

        if ($search !== null && \count($search) > 0) {
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

    /**
     * @param string $type
     * @param string|null $placement
     * @return bool
     * @throws \RuntimeException
     */
    public function hasMessages($type, $placement = null)
    {
        return (bool)\count($this->getMessages($type, $placement));
    }

    /**
     * Set message
     * @param string $message
     * @param string $type
     * @param string|null $placement Key to use if you want the message to be displayed an unique place
     * @param string|null $index
     * @throws \RuntimeException
     */
    protected function setMessage($message, $type, $placement = null, $index = null): void
    {
        $msg = new FormMessage();
        $msg->setMessage($message);
        $msg->setPlacement($placement ?? $this->defaultMessagePlacement);
        $msg->setIndex($index);
        $this->_messages->set($msg, $type);
    }

    /**
     * @param string|null $placement
     * @param string|null $errorType
     * @return bool
     * @throws \RuntimeException
     */
    public function hasErrors($placement = null, $errorType = null)
    {
        return $this->hasMessages($errorType ?? $this->errorType, $placement);
    }

    /**
     * Set error
     * @param string $message
     * @param string|null $placement
     * @throws \RuntimeException
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
     * @throws \RuntimeException
     */
    public function getErrors($placement = null, $errorType = null)
    {
        return $this->getMessages($errorType ?? $this->errorType, $placement);
    }

    /**
     * @param string $placement
     * @return array
     * @throws \RuntimeException
     */
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