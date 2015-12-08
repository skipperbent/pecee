<?php
namespace Pecee;

use Pecee\Widget\Debug\WidgetDebug;

class Debug {

	protected static $instance;
	protected $enabled;
	protected $lastTime;
	protected $stack;
	protected $startTime;

	/**
	 * Get instance of Debug class
	 * @return Debug
	 */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function __construct(){
		$this->enabled = false;
		$this->startTime = microtime(true);
        $this->stack = array();
		$this->add('Debugger initialized.');
	}

	public function __destruct() {
		$this->add('Debugger destructed.');
	}

	protected function getTime() {
		return number_format(microtime(true)-$this->startTime, 10);
	}

	protected function addObject($text) {
        $backtrace = debug_backtrace();

        $line = $backtrace[1]['line'];
        $file = $backtrace[1]['file'];
        $method = $backtrace[1]['function'];
        $class = $backtrace[1]['class'];

        $debug = array();

        for($i=0; $i < count($backtrace)-2; $i++) {
            $trace = array_reverse($backtrace);
            $trace = $trace[$i];
            $tmp = array();
            if(isset($trace['class'])) {
                $tmp['class'] = $trace['class'];
            }

            if(isset($trace['function'])) {
                $tmp['method'] = $trace['function'];
            }

            if(isset($trace['file'])) {
                $tmp['file'] = $trace['file'];
            }

            if(isset($trace['line'])) {
                $tmp['line'] = $trace['line'];
            }

            $debug[] = $tmp;
        }

        $this->stack[]=array('text' => $text, 'time' => $this->getTime(), 'file' => $file, 'line' => $line, 'method' => $method, 'class' => $class, 'debug' => $debug);
		$this->lastTime = microtime(true);
	}

	public function add($text) {
		if(!$this->enabled) {
			return;
		}

		$this->addObject($text);
	}

	public function getEnabled() {
		return $this->enabled;
	}

	public function setEnabled($bool) {
		$this->enabled = $bool;
	}

	public function __toString() {
		$widget = new WidgetDebug($this->stack);
		return $widget->render();
	}

}