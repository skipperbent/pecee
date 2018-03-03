<?php
namespace Pecee\Session;

use Pecee\UI\Form\FormMessage;

class SessionMessage
{
    public const KEY = 'MSG';

    protected $messages;

    public function __construct()
    {
        $this->parse();
    }

    protected function parse()
    {
        $this->messages = Session::get(self::KEY);
    }

    /**
     * @throws \RuntimeException
     */
    public function save()
    {
        Session::set(self::KEY, $this->messages);
    }

    /**
     * @param FormMessage $message
     * @param string|null $type
     * @throws \RuntimeException
     */
    public function set(FormMessage $message, $type = null)
    {
        // Ensure no double posting
        if (isset($this->messages[$type]) && \is_array($this->messages[$type])) {
            if (\in_array($message, $this->messages[$type], true) === false) {
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
     * @return \Pecee\UI\Form\FormMessage|array
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
    public function has($type = null)
    {
        if ($type !== null) {
            return isset($this->messages[$type]) && \count($this->messages[$type]) > 0;
        }

        return \count($this->messages) > 0;
    }

    /**
     * @param string|null $type
     * @throws \RuntimeException
     */
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