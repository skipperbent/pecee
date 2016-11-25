<?php
namespace Pecee;

class Registry
{

    protected static $instance;

    protected $registry = [];

    /**
     * Get instance
     * @return static
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Get key from registry
     * @param string $key
     * @param string|null $default ;
     * @return string|null
     */
    public function get($key, $default = null)
    {
        return (isset($this->registry[$key]) ? $this->registry[$key] : $default);
    }

    /**
     * Set registry key
     * @param string $key
     * @param string $value
     * @return static
     */
    public function set($key, $value)
    {
        $this->registry[$key] = $value;

        return $this;
    }

}