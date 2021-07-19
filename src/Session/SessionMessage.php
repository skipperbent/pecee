<?php
namespace Pecee\Session;

use Pecee\UI\Form\FormMessage;

class SessionMessage
{
    public const KEY = 'MSG';

    protected array $messages;

    public function __construct()
    {
        $this->parse();
    }

    protected function parse(): void
    {
        $this->messages = (array)Session::get(static::KEY, []);
    }

    public function save(): void
    {
        Session::set(static::KEY, $this->messages);
    }

    public function set(FormMessage $message, $type = null): void
    {
        // Ensure no double posting
        if (isset($this->messages[$type]) === true && is_array($this->messages[$type]) === true) {
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
    public function get(?string $type = null, $defaultValue = null)
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
    public function has(?string $type = null)
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
            Session::destroy(static::KEY);
        }
    }
}