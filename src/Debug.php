<?php
namespace Pecee;
use Pecee\UI\Site;

class Debug {
	private static $instance;
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
			self::$instance = new self();
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
		if($this->getEnabled()) {
			$this->addObject($text);
		}
	}

	public function getEnabled() {
		return $this->enabled;
	}

	public function setEnabled($bool) {
		$this->enabled = $bool;
	}

	public function __toString() {

		// TODO: move to WidgetDebug class

		if($this->enabled && count($this->stack) > 0) {
			$output[] = '<h1 style="font-family:Arial;font-size:18px;margin:10px 0px;border-bottom:1px solid #CCC;padding-bottom:5px;">Debug information</h1>
			<table cellspacing="0" cellpadding="0" style="width:100%;font-size:12px;font-family:Arial;">
			<thead style="background-color:#EEE;">
				<tr>
					<th align="left" style="padding:5px;">Execution time</th>
					<th align="left" style="padding:5px;">Message</th>
					<th align="left" style="padding:5px;">Class</th>
					<th align="left" style="padding:5px;">Method</th>
					<th align="left" style="padding:5px;">File</th>
					<th align="center" style="padding:5px;">Line</th>
				</tr>
			</thead>
			<tbody style="background-color:#FFF;">';
			foreach($this->stack as $i=>$log) {
				$output[] = sprintf('<tr style="border-bottom:1px solid #CCC;cursor:pointer;height:10px;" onclick="show_debug(\'debug_'.$i.'\')">
				<td style="vertical-align: top;padding:5px;">
				    %s
                </td>
				<td style="vertical-align: top;padding:5px;">
				    %s
				    <div id="debug_'.$i.'" style="display: none;background-color:#EEE;padding:10px;margin-top:10px;">
                        <pre>%s</pre>
                    </div>
				</td>
				<td style="vertical-align:top; padding:5px;">%s</td>
				<td style="vertical-align:top; padding:5px;">%s</td>
				<td style="vertical-align:top; padding:5px;">%s</td>
				<td style="vertical-align:top; padding:5px;" align="center">%s</td>
			</tr>', $log['time'], $log['text'], print_r($log['debug'],true), $log['class'], $log['method'], $log['file'], $log['line']);
			}
			$output[] = '</tbody></table><script> function show_debug(id) {
                var el = document.getElementById(id);
                document.getElementById(id).style.display = (el.style.display == \'block\') ? \'none\' : \'block\';
            } </script>';
			return join('', $output);
		}
		return '';
	}

}