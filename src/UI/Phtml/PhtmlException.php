<?php
namespace Pecee\UI\Phtml;
class PhtmlException extends \Exception {

    private $phtml;
    private $chr;
    private $lineNum;
    private $chrNum;
    private $debugTrace;

	public function __construct($phtml, $chr, $lineNum, $chrNum, $debugTrace) {
		$this->phtml = $phtml;
		$this->chr = $chr;
		$this->lineNum = $lineNum;
		$this->chrNum = $chrNum;
		$this->debugTrace = $debugTrace;
		parent::__construct(sprintf('Failed parsing PHTML at line %s:%s - CHR: <pre>"%s"</pre>',$lineNum,$chrNum,$chr),E_ERROR);
	}
	public function __toString() {
		$lines = explode("\n",$this->phtml);
		$i = 1;
		foreach($lines as &$line) {
			$spaces = str_repeat(' ',3-strlen("$i"));
			$line = "<strong>$spaces$i:</strong> ".htmlentities($line,ENT_QUOTES,'UTF-8');
			$i++;
		}
		$phtml = implode("\n",$lines);
		return sprintf('<div><strong>%s</strong><pre>%s</pre></div>',
				$this->getMessage(),
				$phtml.chr(10).chr(10).$this->getTraceAsString().
				chr(10).chr(10).'###### DEBUG TRACE ######'.
				$this->debugTrace
		);
	}
}