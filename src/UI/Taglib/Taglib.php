<?php
namespace Pecee\UI\Taglib;

class Taglib implements ITaglib
{

    private const TAG_PREFIX = 'tag';

    protected $body = '';
    protected $bodies = [];
    protected $preProcess;
    protected $currentTag;

    public function __construct($preProcess = false)
    {
        $this->preProcess = $preProcess;
    }

    /**
     * @param string $tag
     * @param array $attrs
     * @param string $body
     * @return string
     * @throws \InvalidArgumentException
     */
    public function callTag($tag, array $attrs, $body = '')
    {
        $this->currentTag = $tag;
        $method = static::TAG_PREFIX . ucfirst($tag);

        if (method_exists($this, $method) === false) {
            throw new \InvalidArgumentException('Unknown tag: ' . $tag . ' in ' . static::class);
        }

        $this->body = $body;
        $this->bodies[] = $body;

        return $this->$method((object)$attrs);
    }

    /**
     * @param string $tag
     * @param array $args
     * @throws \InvalidArgumentException
     */
    public function __call($tag, $args)
    {
        $this->callTag($tag, $args[0], $args[1]);
    }

    /**
     * @param \stdClass $attrs
     * @param array $name
     * @throws \ErrorException
     */
    protected function requireAttributes(\stdClass $attrs, array $name)
    {
        $errors = [];
        foreach ($name as $n) {
            if (isset($attrs->$n) === false) {
                $errors[] = $n;
            }
        }
        if (\count($errors) > 0) {
            throw new \ErrorException('The current tag "' . $this->currentTag . '" requires the attribute(s): ' . implode(', ', $errors));
        }
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function isPreprocess()
    {
        return $this->preProcess;
    }

}