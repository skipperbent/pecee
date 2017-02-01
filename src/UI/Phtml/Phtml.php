<?php
namespace Pecee\UI\Phtml;

/**
 * The PHTML parsing and compilation engine
 */
class Phtml
{

    const SETTINGS_TAGLIB = 'SETTINGS_TAGLIB';
    const NOTHING = 'NOTHING';
    const STRING = 'STRING';
    const TAG = 'TAG';
    const TAGEND = 'TAGEND';
    const DOCTYPE = 'DOCTYPE';
    const ATTR = 'ATTR';
    const SCRIPT = 'SCRIPT';
    const PHP = 'PHP';
    const P_EVAL = 'P_EVAL';
    const COMMENT = 'COMMENT';

    private static $IGNORELIST = [self::PHP, self::COMMENT, self::STRING, self::P_EVAL, self::DOCTYPE];
    private static $IGNOREALLLIST = [self::PHP, self::COMMENT, self::STRING, self::P_EVAL, self::DOCTYPE];
    private static $SCRIPTAGS = ['script', 'style', 'inline'];

    private $withinStack = [];
    private $current = '';
    private $currentIgnore = '';
    private $node;
    private $lastChar, $nextChar, $char, $attrName;
    private $debug = false;
    private $stringStartChar = '';
    private $charCount = 0;
    private $lineCount = 0;
    private $phtmlRaw = '';
    private $debugTrace = '';
    private $ignoreNextChar = false;
    private $ignoreChars = false;
    private $conditionalComment = false;
    private $prevChar;

    /**
     * @param phtml $string
     * @return PhtmlNode
     */
    public function read($string)
    {
        $string = trim($string);
        $this->withinStack = [self::NOTHING];
        $this->current = '';
        $this->debugTrace = '';
        $this->node = new PhtmlNode();
        $this->node->setContainer(true);
        $this->node->setTag('phtml');
        $this->phtmlRaw = $string;
        $this->lineCount = 1;
        $ignoredAscii = [10, 13, ord("\t"), ord(" ")];
        for ($i = 0, $max = mb_strlen($string); $i < $max; $i++) {
            $chr = mb_substr($string, $i, 1);
            $this->charCount++;
            $this->prevChar = null;
            $this->nextChar = mb_substr($string, $i + 1, 1);
            $this->char = $chr;

            if ($this->char == "\n") {
                $this->lineCount++;
                $this->charCount = 0;
            }

            switch ($chr) {
                case '<':
                    if (mb_substr($string, $i + 1, 3) == '!--') {
                        $this->pushWithin(self::COMMENT);
                        break;
                    }
                    if ($this->nextChar == '?') {
                        $this->pushWithin(self::PHP);
                        break;
                    }
                    if ($this->nextChar == '/' && !$this->ignoreAll()) {
                        $this->ignoreChars = true;
                        $this->pushWithin(self::TAGEND);
                        $this->getNode()->setContainer(true);

                    } elseif ($this->nextChar == '!') {
                        $this->pushWithin(self::DOCTYPE);
                    } elseif (preg_match('/[A-Z0-9]/i', $this->nextChar) && !$this->isWithin(self::SCRIPT)) {
                        $this->onTagStart();
                    }

                    break;
                case '>':
                    if (mb_substr($string, $i - 2, 2) == '--' && $this->isWithin(self::COMMENT)) {
                        $this->popWithin();
                        break;
                    }
                    //Handle conditional comments
                    if (($this->conditionalComment && $this->lastChar == ']') && $this->isWithin(self::COMMENT)) {
                        $this->conditionalComment = false;
                        $this->popWithin();
                        break;
                    }
                    if ($this->lastChar == '?' && $this->isWithin(self::PHP)) {
                        $this->popWithin();
                    } elseif ($this->isWithin(self::TAGEND)) {
                        $this->checkEndTag();
                        $this->popWithin();
                        $this->onNodeEnd();
                        $this->ignoreChars = false;
                        $this->ignoreNextChar(1);
                    } elseif ($this->isWithin(self::DOCTYPE)) {
                        $this->popWithin();
                        $this->onWordEnd();
                    } elseif (!$this->ignoreTags()) {
                        $this->onWordEnd();
                        if ($this->isWithin(self::TAG)) {
                            $this->ignoreNextChar(2);
                        }
                        $this->onTagEnd();
                    }

                    break;
                case ':':
                    if ($this->isWithin(self::ATTR)) {
                        break;
                    }
                case '%':
                    if ($this->ignoreTags()) {
                        break;
                    }
                    if ($this->nextChar == '{') {
                        $this->pushWithin(self::P_EVAL);
                        break;
                    }
                case '}':
                    if ($this->lastChar != '\\' && $this->isWithin(self::P_EVAL)) {
                        $this->popWithin();
                        break;
                    }

                case ' ':
                case "\t":
                case "\n":
                case "\r":
                case '/':
                case '=':
                    if ($this->ignoreTags() && !$this->isWithin(self::ATTR)) {
                        break;
                    }
                    $this->onWordEnd();
                    break;
                case '"':
                case '\'':
                    if (!$this->isWithin(self::TAG, true)) {
                        break;
                    }
                    if ($this->isWithin(self::STRING)) {
                        $this->onStringEnd();
                    } else {
                        $this->onStringStart();
                    }
                    break;
                case '[':
                    if (mb_substr($string, $i - 4, 4) == '<!--' && $this->isWithin(self::COMMENT)) {
                        $this->conditionalComment = true;
                    }
                default:
                    $this->onWordStart();

                    break;
            }
            $ascii = ord($this->char);
            if (in_array($ascii, $ignoredAscii)) {
                $this->debug("CHR:chr($ascii)");
            } else {
                $this->debug("CHR:$this->char");
            }
            $this->addChar($this->char);
            $this->lastChar = $this->char;
        }
        $text = $this->getCurrent();
        if ($text) {
            $this->getNode()->addChild(new PhtmlNodeText($text));
        }

        $node = $this->getNode();
        $this->clear();

        return $node;
    }

    protected function ignoreAll()
    {
        return in_array($this->within(), self::$IGNOREALLLIST);
    }

    protected function ignoreTags()
    {
        return in_array($this->within(), self::$IGNORELIST);
    }

    protected function ignoreNextChar($debugKey)
    {
        $this->debug('IGNORING NEXT CHR (' . $debugKey . '): ' . $this->char);
        $this->ignoreNextChar = true;//Used because the tag is marked ended before the char is added
    }

    protected function clear()
    {
        $this->node = null;
        $this->withinStack = [];
        $this->current = '';
        $this->node;
        $this->lastChar = '';
        $this->nextChar = '';
        $this->char = '';
        $this->attrName = '';
    }

    protected function addChar($chr)
    {
        if (!$this->ignoreNextChar && !$this->ignoreChars) {
            $this->current .= $chr;
        }
        $this->ignoreNextChar = false;
        $this->currentIgnore .= $chr;
    }

    protected function onStringStart()
    {
        if ($this->stringStartChar && $this->stringStartChar != $this->char) {
            return;
        }
        $this->stringStartChar = $this->char;
        $this->debug("STRING START");

        $this->pushWithin(self::STRING);
        $this->current = substr($this->current, 1);
    }

    protected function onStringEnd()
    {
        if ($this->stringStartChar != $this->char || $this->lastChar == '\\') {
            return;
        }
        $this->stringStartChar = '';
        $this->debug("STRING END");
        $this->popWithin();
    }

    protected function getCurrent($alphanum = false, $erase = true)
    {
        $result = $this->current;
        if ($alphanum) {
            $result = preg_replace('/[^A-Z0-9_\-]/i', '', $result);
        }
        if ($erase) {
            $this->clearCurrent();
        }

        return $result;
    }

    protected function clearCurrent()
    {
        $this->current = '';
        $this->currentIgnore = '';
    }

    protected function onWordStart()
    {
        if ($this->isWithin(self::STRING)) {
            return;
        }//ignore
        switch ($this->within()) {
            case self::TAG:
                if ($this->getNode()->getTag()) {
                    $this->pushWithin(self::ATTR);
                }
                break;
        }
    }

    protected function hasCurrent()
    {
        return trim($this->current) != '';
    }

    protected function onWordEnd()
    {
        if ($this->char != ':') {
            $this->currentIgnore = '';
        }
        if (!$this->hasCurrent()) {
            return;
        } //ignore

        switch ($this->within()) {
            case self::TAG:
                if ($this->ignoreTags()) {
                    return;
                }
                $current = $this->getCurrent(true);
                if (!$current) {
                    return;
                }
                if ($this->char == ':') {
                    $this->getNode()->setNs($current);
                } else {
                    $this->getNode()->setTag($current);
                    $this->debug("STARTING NODE: '" . $this->getNode()->getTag() . "'");
                    if ($this->isScriptTag($current)) {
                        $this->pushBefore(self::SCRIPT);
                    }
                }
                break;
            case self::ATTR:
                if (!$this->attrName) {
                    $current = preg_replace('/[^A-Z0-9_\-\:]/i', '', $this->getCurrent(false));
                    if (!$current) {
                        return;
                    }
                    $this->attrName = $current;
                    $this->debug("ATTR FOUND: $this->attrName");
                } else {
                    $val = $this->getCurrent();
                    $val = substr($val, 1, strlen($val) - 2);
                    $this->debug("ATTR VAL FOUND FOR: $this->attrName ($val)");
                    $this->getNode()->setAttribute($this->attrName, $val);
                    $this->attrName = '';
                    $this->popWithin();
                }

                break;
        }
    }

    protected function isScriptTag($tag)
    {
        return in_array(strtolower($tag), self::$SCRIPTAGS);
    }

    protected function pushWithin($within)
    {
        $this->withinStack[] = $within;
    }

    protected function pushBefore($within)
    {
        $oldWithin = $this->popWithin();
        $this->pushWithin($within);
        $this->pushWithin($oldWithin);
    }

    protected function popWithin()
    {
        return array_pop($this->withinStack);
    }

    protected function within()
    {
        return $this->withinStack[count($this->withinStack) - 1];
    }

    protected function isWithin($within, $deep = false)
    {
        if ($deep) {
            return in_array($within, $this->withinStack);
        } else {
            return $this->within() == $within;
        }
    }

    protected function onTagStart()
    {
        if ($this->ignoreTags()) {
            return;
        }
        $node = new PhtmlNode();
        $text = $this->getCurrent();
        $this->pushWithin(self::TAG);
        if ($text) {
            $this->getNode()->addChild(new PhtmlNodeText($text));
        }
        $this->getNode()->addChild($node);
        $this->node = $node;
    }

    protected function onTagEnd()
    {
        if (!$this->isWithin(self::TAG)) {
            return;
        }
        if ($this->ignoreTags()) {
            return;
        }
        $this->debug("ENDING TAG: " . $this->getNode()->getTag());

        if ($this->lastChar == '/') {
            $this->onNodeEnd();
        }

        $this->popWithin();
    }

    protected function checkEndTag()
    {
        $endTag = trim($this->currentIgnore, "</> \t\n\r");
        if ($endTag) {
            $ns = '';
            if (!stristr($endTag, ':')) {
                $tag = $endTag;
            } else {
                list($ns, $tag) = explode(':', $endTag);
            }

            if (strtolower($this->getNode()->getTag()) != strtolower($tag) ||
                strtolower($this->getNode()->getNs()) != strtolower($ns)
            ) {
                $this->debug("ERROR WRONG END TAG: $endTag ($ns:$tag) for " . $this->getNode()->getTag());
            }
        }
    }

    protected function onNodeEnd()
    {
        if ($this->ignoreAll()) {
            return;
        }
        $parent = $this->getNode()->getParent();

        $text = $this->getCurrent();
        if ($text) {
            $this->getNode()->addChild(new PhtmlNodeText($text));
        }
        $this->debug("ENDING NODE: " . $this->getNode()->getTag());

        if ($this->isScriptTag($this->getNode()->getTag())) {
            $this->popWithin(); //Pop an extra time for the SCRIPT
        }
        $this->node = $parent;
    }

    /**
     * @return PhtmlNode
     * @throws PhtmlException
     */
    protected function getNode()
    {
        if (!$this->node) {
            throw new PhtmlException($this->phtmlRaw, $this->char, $this->lineCount, $this->charCount, $this->debugTrace);
        }

        return $this->node;
    }

    protected function debug($msg)
    {
        $msg = htmlentities("[" . implode(',', $this->withinStack) . "] " . $msg);
        if ($this->debug) {
            echo "\n$msg";
        } else {
            $this->debugTrace .= "\n$msg";
        }
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }
}