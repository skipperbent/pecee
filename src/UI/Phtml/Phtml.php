<?php

namespace Pecee\UI\Phtml;

/**
 * The PHTML parsing and compilation engine
 */
class Phtml
{

    public const SETTINGS_TAGLIB = 'SETTINGS_TAGLIB';

    private const NOTHING = 'NOTHING';
    private const STRING = 'STRING';
    private const TAG = 'TAG';
    private const TAGEND = 'TAGEND';
    private const DOCTYPE = 'DOCTYPE';
    private const ATTR = 'ATTR';
    private const SCRIPT = 'SCRIPT';
    private const PHP = 'PHP';
    private const P_EVAL = 'P_EVAL';
    private const COMMENT = 'COMMENT';

    private static $IGNORELIST = [
        self::PHP,
        self::COMMENT,
        self::STRING,
        self::P_EVAL,
        self::DOCTYPE
    ];

    private static $IGNOREALLLIST = [
        self::PHP,
        self::COMMENT,
        self::STRING,
        self::P_EVAL,
        self::DOCTYPE
    ];

    public static $SCRIPTAGS = [
        'script',
        'style',
        'inline'
    ];

    public static $VOIDTAGS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'menuitem',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    ];

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
     * @throws PhtmlException
     * @return PhtmlNode
     */
    public function read($string)
    {
        $string = trim($string);
        $this->withinStack = [static::NOTHING];
        $this->current = '';
        $this->debugTrace = '';
        $this->node = new PhtmlNode();
        $this->node->setContainer(false);
        $this->node->setTag('phtml');
        $this->phtmlRaw = $string;
        $this->lineCount = 1;
        $ignoredAscii = [10, 13, \ord("\t"), \ord(' ')];
        for ($i = 0, $max = mb_strlen($string); $i < $max; $i++) {
            $chr = mb_substr($string, $i, 1);
            $this->charCount++;
            $this->prevChar = null;
            $this->nextChar = mb_substr($string, $i + 1, 1);
            $this->char = $chr;

            if ($this->char === "\n") {
                $this->lineCount++;
                $this->charCount = 0;
            }

            switch ($chr) {
                case '<':
                    if (mb_substr($string, $i + 1, 3) == '!--') {
                        $this->pushWithin(static::COMMENT);
                        break;
                    }
                    if ($this->nextChar == '?') {
                        $this->pushWithin(static::PHP);
                        break;
                    }
                    if ($this->nextChar == '/' && !$this->ignoreAll()) {
                        $this->ignoreChars = true;
                        $this->pushWithin(static::TAGEND);
                        $this->getNode()->setContainer(true);

                    } elseif ($this->nextChar == '!') {
                        $this->pushWithin(static::DOCTYPE);
                    } elseif (preg_match('/[A-Z0-9]/i', $this->nextChar)) {
                        $this->onTagStart();
                    }

                    break;
                case '>':

                    if (mb_substr($string, $i - 2, 2) == '--' && $this->isWithin(static::COMMENT)) {
                        $this->popWithin();
                        break;
                    }

                    //Handle conditional comments
                    if (($this->conditionalComment && $this->lastChar == ']') && $this->isWithin(static::COMMENT)) {
                        $this->conditionalComment = false;
                        $this->popWithin();
                        break;
                    }
                    if ($this->lastChar == '?' && $this->isWithin(static::PHP)) {
                        $this->popWithin();
                    } elseif ($this->isWithin(static::TAGEND)) {

                        $this->checkEndTag();
                        $this->popWithin();
                        $this->onNodeEnd();
                        $this->ignoreChars = false;
                        $this->ignoreNextChar(1);

                    } elseif ($this->isWithin(static::DOCTYPE)) {
                        $this->popWithin();
                        $this->onWordEnd();
                    } elseif (!$this->ignoreTags()) {
                        $this->onWordEnd();
                        if ($this->isWithin(static::TAG)) {
                            $this->ignoreNextChar(2);
                        }
                        $this->onTagEnd();
                    }

                    break;
                case ':':
                    if ($this->isWithin(static::ATTR)) {
                        break;
                    }
                case '%':
                    if ($this->ignoreTags()) {
                        break;
                    }
                    if ($this->nextChar == '{') {
                        $this->pushWithin(static::P_EVAL);
                        break;
                    }
                case '}':
                    if ($this->lastChar != '\\' && $this->isWithin(static::P_EVAL)) {
                        $this->popWithin();
                        break;
                    }

                case ' ':
                case "\t":
                case "\n":
                case "\r":
                case '/':
                case '=':
                    if ($this->ignoreTags() && !$this->isWithin(static::ATTR)) {
                        break;
                    }
                    $this->onWordEnd();
                    break;
                case '"':
                case '\'':
                    if (!$this->isWithin(static::TAG, true)) {
                        break;
                    }
                    if ($this->isWithin(static::STRING)) {
                        $this->onStringEnd();
                    } else {
                        $this->onStringStart();
                    }
                    break;
                case '[':
                    if (mb_substr($string, $i - 4, 4) == '<!--' && $this->isWithin(static::COMMENT)) {
                        $this->conditionalComment = true;
                    }
                default:
                    $this->onWordStart();

                    break;
            }
            $ascii = \ord($this->char);
            if (\in_array($ascii, $ignoredAscii, true) === true) {
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
        return \in_array($this->within(), static::$IGNOREALLLIST, true);
    }

    protected function ignoreTags()
    {
        return \in_array($this->within(), static::$IGNORELIST, true);
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
        $this->debug('STRING START');

        $this->pushWithin(static::STRING);
        $this->current = substr($this->current, 1);
    }

    protected function onStringEnd()
    {
        if ($this->stringStartChar != $this->char || $this->lastChar == '\\') {
            return;
        }
        $this->stringStartChar = '';
        $this->debug('STRING END');
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

    /**
     * @throws PhtmlException
     */
    protected function onWordStart()
    {
        if ($this->isWithin(static::STRING)) {
            return;
        }//ignore
        switch ($this->within()) {
            case static::TAG:
                if ($this->getNode()->getTag()) {
                    $this->pushWithin(static::ATTR);
                }
                break;
        }
    }

    protected function hasCurrent()
    {
        return trim($this->current) != '';
    }

    /**
     * @throws PhtmlException
     */
    protected function onWordEnd()
    {
        if ($this->char != ':') {
            $this->currentIgnore = '';
        }
        if (!$this->hasCurrent()) {
            return;
        } //ignore

        switch ($this->within()) {
            case static::TAG:
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
                        $this->pushBefore(static::SCRIPT);
                    }
                }
                break;
            case static::ATTR:
                if (!$this->attrName) {
                    $current = preg_replace('/[^A-Z0-9_\-\:]/i', '', $this->getCurrent(false));
                    if (!$current) {
                        return;
                    }
                    $this->attrName = $current;
                    $this->debug("ATTR FOUND: $this->attrName");

                    if ($this->nextChar === '') {
                        $this->getNode()->setAttribute($this->attrName);
                        $this->attrName = '';
                        $this->popWithin();
                    }
                } else {
                    $val = $this->getCurrent();
                    $val = substr($val, 1, -1);
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
        return \in_array(strtolower($tag), static::$SCRIPTAGS, true);
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
        return $this->withinStack[\count($this->withinStack) - 1];
    }

    protected function isWithin($within, $deep = false)
    {
        if ($deep === true) {
            return \in_array($within, $this->withinStack, true);
        }

        return $this->within() === $within;
    }

    /**
     * @throws PhtmlException
     */
    protected function onTagStart()
    {
        if ($this->ignoreTags()) {
            return;
        }
        $node = new PhtmlNode();
        $text = $this->getCurrent();
        $this->pushWithin(static::TAG);
        if ($text) {
            $this->getNode()->addChild(new PhtmlNodeText($text));
        }
        $this->getNode()->addChild($node);
        $this->node = $node;
    }

    protected function isVoidTag($tag)
    {
        return \in_array($tag, static::$VOIDTAGS, true);
    }

    /**
     * @throws PhtmlException
     */
    protected function onTagEnd()
    {
        if (!$this->isWithin(static::TAG)) {
            return;
        }
        if ($this->ignoreTags()) {
            return;
        }
        $this->debug('ENDING TAG: ' . $this->getNode()->getTag());

        if ($this->lastChar == '/' || $this->isVoidTag($this->getNode()->getTag())) {
            $this->onNodeEnd();
        }

        $this->popWithin();
    }

    /**
     * @throws PhtmlException
     */
    protected function checkEndTag()
    {
        $endTag = trim($this->currentIgnore, "</> \t\n\r");
        if ($endTag) {
            $ns = '';
            if (false === strpos($endTag, ':')) {
                $tag = $endTag;
            } else {
                [$ns, $tag] = explode(':', $endTag);
            }

            if (strtolower($this->getNode()->getTag()) != strtolower($tag) ||
                strtolower($this->getNode()->getNs()) != strtolower($ns)
            ) {
                $this->debug("ERROR WRONG END TAG: $endTag ($ns:$tag) for " . $this->getNode()->getTag());
            }
        }
    }

    /**
     * @throws PhtmlException
     */
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
        $this->debug('ENDING NODE: ' . $this->getNode()->getTag());

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
        $msg = htmlentities('[' . implode(',', $this->withinStack) . '] ' . $msg);
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