<?php

namespace Pecee\Application;

use Pecee\Widget\Debug\WidgetDebug;

class Debug
{
    protected $lastTime;
    protected $stack;
    protected $startTime;
    protected $backLevel = 3;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->stack = [];
        $this->add('Debugger initialized.');
    }

    public function __destruct()
    {
        $this->add('Debugger destructed.');
    }

    protected function getTime()
    {
        return number_format(microtime(true) - $this->startTime, 10);
    }

    protected function addObject($text)
    {
        $backtrace = debug_backtrace();

        $line = $backtrace[1]['line'];
        $file = $backtrace[1]['file'];
        $method = $backtrace[1]['function'];
        $class = $backtrace[1]['class'];

        $debug = [];

        for ($i = 0; $i < count($backtrace) - $this->backLevel; $i++) {
            $trace = array_reverse($backtrace);
            $trace = $trace[$i];
            $tmp = [];

            if (isset($trace['class'])) {
                $tmp['class'] = $trace['class'];
            }

            if (isset($trace['function'])) {
                $tmp['method'] = $trace['function'];
            }

            if (isset($trace['file'])) {
                $tmp['file'] = $trace['file'];
            }

            if (isset($trace['line'])) {
                $tmp['line'] = $trace['line'];
            }

            $debug[] = $tmp;
        }

        $this->stack[] = [
            'text'   => $text,
            'time'   => $this->getTime(),
            'file'   => $file,
            'line'   => $line,
            'method' => $method,
            'class'  => $class,
            'debug'  => $debug,
        ];

        $this->lastTime = microtime(true);
    }

    /**
     * Add debug entry
     * @param string $text
     * @param array $args
     */
    public function add($text, array $args = [])
    {
        $this->addObject(vsprintf(str_replace('%', '%%', $text), $args));
    }

    public function __toString()
    {
        $widget = new WidgetDebug($this->stack);

        return $widget->render();
    }

}