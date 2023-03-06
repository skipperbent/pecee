<?php

namespace Pecee;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputItem;
use Pecee\Session\Session;
use Pecee\Session\SessionMessage;
use Pecee\UI\Form\FormMessage;
use Pecee\UI\Site;

abstract class Base
{
    protected string $errorType = 'danger';
    protected string $defaultMessagePlacement = 'default';
    protected string $_inputSessionKey = 'InputValues';
    protected string $_sessionMessagePrefix = '';
    protected ?SessionMessage $_sessionMessage = null;

    protected function onInputError(IInputItem $input, string $error): void
    {

    }

    /**
     * @return SessionMessage
     */
    public function sessionMessage(): SessionMessage
    {
        if ($this->_sessionMessage === null) {
            $this->_sessionMessage = new SessionMessage($this->_sessionMessagePrefix);
        }

        return $this->_sessionMessage;
    }

    protected function setInputValues(): void
    {
        $values = Session::get($this->_inputSessionKey, []);

        /* @var array $values */
        foreach ($values as $key => $value) {
            $item = input()->find($key, ['get', 'post']) ?? new InputItem($key);
            $item->setValue((string)$value);

            if (request()->getMethod() === 'post') {
                input()->addPost($key, $item);
            } else {
                input()->addGet($key, $item);
            }
        }

        Session::destroy($this->_inputSessionKey);
    }

    public function setInputName(array $names): void
    {
        foreach ($names as $key => $name) {
            $item = input()->find($key);

            if ($item !== null) {
                $item->setName($name);
            }
        }
    }

    public function saveInputValues(array $values = null): void
    {
        if ($values === null) {
            $values = input()->all();
        }

        Session::set($this->_inputSessionKey, $values);
    }

    protected function validate(array $validation): void
    {
        $this->performValidation($validation);
    }

    protected function performValidation(array $validation): void
    {
        foreach ($validation as $key => $validations) {

            $input = input()->find($key) ?? new InputItem($key, null);
            $inputs = ($input instanceof IInputItem) ? [$input] : $input;
            $validations = is_array($validations) === false ? [$validations] : $validations;

            /* @var $validateClass \Pecee\UI\Form\Validation\ValidateInput */
            foreach ($validations as $validateClass) {
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

    protected function appendSiteTitle(string $title, ?string $separator = '-'): void
    {
        $separator = ($separator === null) ? '' : ' ' . $separator . ' ';
        app()->site->setTitle(app()->site->getTitle() . $separator . $title);
    }

    protected function prependSiteTitle(string $title, string $separator = ' - '): void
    {
        app()->site->setTitle($title . $separator . app()->site->getTitle());
    }

    /**
     * Checks if there has been a form post-back
     * @return bool
     */
    public function isPostBack(): bool
    {
        return request()->getMethod() !== 'get';
    }

    /**
     * Get site
     * @return Site
     */
    public function getSite(): Site
    {
        return app()->site;
    }

    /**
     * Get form message
     * @param string $type
     * @param string|null $placement
     * @return FormMessage|null
     */
    public function getMessage(string $type, ?string $placement = null): ?FormMessage
    {
        $messages = $this->getMessages($type, $placement);

        return \count($messages) > 0 ? $messages[0] : null;
    }

    /**
     * Get form messages
     * @param string|null $type
     * @param string|null $placement
     * @return array|FormMessage[]
     */
    public function getMessages(?string $type = null, ?string $placement = null): array
    {
        $messages = [];
        $search = $this->sessionMessage()->get($type);

        if ($search !== null && count($search) > 0) {
            foreach ($search as $message) {
                if ($placement === null || $message->getPlacement() === $placement) {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }

    public function hasMessages(?string $type = null, ?string $placement = null): bool
    {
        return (\count($this->getMessages($type, $placement)) > 0);
    }

    /**
     * Set message
     * @param string $message
     * @param string $type
     * @param string|null $placement Key to use if you want the message to be displayed an unique place
     * @param string|null $index
     */
    protected function setMessage(string $message, string $type, ?string $placement = null, ?string $index = null): void
    {
        $msg = new FormMessage();
        $msg->setMessage($message);
        $msg->setPlacement($placement ?? $this->defaultMessagePlacement);
        $msg->setIndex($index);
        $this->sessionMessage()->set($msg, $type);
    }

    public function hasErrors(?string $placement = null, ?string $errorType = null): bool
    {
        return $this->hasMessages($errorType ?? $this->errorType, $placement);
    }

    /**
     * Set error
     *
     * @param string $message
     * @param string|null $placement
     */
    protected function setError(string $message, ?string $placement = null): void
    {
        $this->setMessage($message, $this->errorType, $placement);
    }

    /**
     * Get error messages
     * @param string|null $placement
     * @param string|null $errorType
     * @return array
     */
    public function getErrors(?string $placement = null, ?string $errorType = null): array
    {
        return $this->getMessages($errorType ?? $this->errorType, $placement);
    }

    public function getErrorsArray(?string $placement = null): array
    {
        $output = [];

        foreach ($this->getMessages($this->errorType, $placement) as $error) {
            $output[] = $error->getMessage();
        }

        return $output;
    }

    public function getValidationFor(string $index): ?string
    {
        $search = $this->sessionMessage()->get($this->errorType);

        if ($search !== null) {
            foreach ($search as $message) {
                if ($message->getIndex() === $index) {
                    return $message->getMessage();
                }
            }
        }

        return null;
    }

}