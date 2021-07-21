<?php

namespace Pecee\Application;

use Pecee\Widget\Debug\WidgetDebug;

class Debug
{
    protected float $lastTime = 0;
    protected array $stack = [];
    protected float $startTime = 0;
    protected int $backLevel = 3;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->add('Debugger initialized.');
    }

    public function __destruct()
    {
        $this->add('Debugger destructed.');
    }

    protected function getTime(): float
    {
        return number_format(microtime(true) - $this->startTime, 10);
    }

    protected function addObject($text): void
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
     * @param array ...$args
     */
    public function add(string $text, ...$args): void
    {
        $this->addObject(vsprintf(str_replace('%', '%%', $text), $args));
    }

    public function __toString(): string
    {
        $widget = new WidgetDebug($this->stack);

        return (string)$widget->render();
    }

}