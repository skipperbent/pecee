<?php
namespace Pecee\UI\Phtml;

class PhtmlException extends \Exception
{
    private $pHtml;
    private $debugTrace;

    public function __construct($pHtml, $chr, $lineNum, $chrNum, $debugTrace)
    {
        $this->pHtml = $pHtml;
        $this->debugTrace = $debugTrace;
        parent::__construct(sprintf('Failed parsing PHTML at line %s:%s - CHR: <pre>"%s"</pre>', $lineNum, $chrNum, $chr), E_ERROR);
    }

    public function __toString()
    {
        $lines = explode(\chr(10), $this->pHtml);
        $i = 1;
        foreach ($lines as &$line) {
            $spaces = str_repeat(' ', 3 - \strlen((string)$i));
            $line = "<strong>$spaces$i:</strong> " . htmlentities($line, ENT_QUOTES, 'UTF-8');
            $i++;
        }
        $pHtml = implode("\n", $lines);

        return sprintf('<div><strong>%s</strong><pre>%s</pre></div>',
            $this->getMessage(),
            $pHtml . \chr(10) . \chr(10) . $this->getTraceAsString() .
            \chr(10) . \chr(10) . '###### DEBUG TRACE ######' .
            $this->debugTrace
        );
    }

}