<?php

namespace Pecee\Application;

use Pecee\Widget\Debug\WidgetDebug;

class Debug
{
    protected array $stack;
    protected float $lastTime;
    protected float $startTime;
    protected int $backLevel = 3;
    protected array $groups = [];

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->lastTime = $this->startTime;
        $this->stack = [];
        $this->add('debug','Debugger initialized.');
    }

    public function __destruct()
    {
        $this->add('debug', 'Debugger destructed.');
    }

    protected function addObject($text, ?string $group = null)
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

        $time = microtime(true);
        $groupTime = 0;

        if ($group !== null) {
            if (isset($this->groups[$group])) {
                $groupTime = $time - $this->groups[$group];
            } else {
                $this->groups[$group] = $time;
            }
        }

        $this->stack[] = [
            'text' => $text,
            'group' => $group,
            'group_time' => $groupTime,
            'time' => $time - $this->startTime,
            'time_last' => $time - $this->lastTime,
            'file' => $file,
            'line' => $line,
            'method' => $method,
            'class' => $class,
            'debug' => $debug,
        ];

        $this->lastTime = $time;
    }

    /**
     * Add debug entry with group
     * @param string $group
     * @param string $text
     * @param ...$args
     * @return void
     */
    public function add(string $group, string $text, ...$args): void
    {
        $this->addObject(vsprintf(str_replace('%', '%%', $text), $args), $group);
    }

    public function __toString(): string
    {
        return (new WidgetDebug($this->stack))->render();
    }

}