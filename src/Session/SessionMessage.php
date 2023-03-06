<?php

namespace Pecee\Session;

use Pecee\UI\Form\FormMessage;

class SessionMessage
{
    public const KEY = 'MSG';

    protected ?array $messages = null;
    protected string $prefix = '';

    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;
        $this->parse();
    }

    protected function getKey(): string
    {
        return $this->prefix . static::KEY;
    }

    protected function parse()
    {
        $this->messages = Session::get($this->getKey());
    }

    public function save(): void
    {
        Session::set($this->getKey(), $this->messages);
    }

    public function set(FormMessage $message, $type = null): void
    {
        // Ensure no double posting
        if (isset($this->messages[$type]) === true && \is_array($this->messages[$type]) === true) {
            if (in_array($message, $this->messages[$type], true) === false) {
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
     * @param mixed|null $defaultValue
     * @return \Pecee\UI\Form\FormMessage|array|FormMessage[]
     */
    public function get($type = null, $defaultValue = null)
    {
        if ($type !== null) {
            return $this->messages[$type] ?? $defaultValue;
        }

        return $this->messages;
    }

    /**
     * Checks if there's any messages
     * @param string|null $type
     * @return boolean
     */
    public function has($type = null): bool
    {
        if ($type !== null) {
            return isset($this->messages[$type]) && count($this->messages[$type]) > 0;
        }

        return count($this->messages) > 0;
    }

    public function clear($type = null): void
    {
        if ($type !== null) {
            unset($this->messages[$type]);
            $this->save();
        } else {
            Session::destroy($this->getKey());
        }
    }
}