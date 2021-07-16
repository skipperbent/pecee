<?php

namespace Pecee\UI\Taglib;

class Taglib implements ITaglib
{

    public const TAG_PREFIX = 'tag';

    protected ?string $body = null;
    protected array $bodies = [];
    protected bool $preProcess = false;
    protected string $currentTag = '';

    public function __construct(bool $preProcess = false)
    {
        $this->preProcess = $preProcess;
    }

    protected function renderPhp(string $content): string
    {
        ob_start();
        eval('?>' . $content);

        return ob_get_clean();
    }

    public function callTag(string $tag, array $attrs, ?string $body = null)
    {
        $this->currentTag = $tag;
        $method = static::TAG_PREFIX . ucfirst($tag);

        if (method_exists($this, $method) === false) {
            throw new \InvalidArgumentException('Unknown tag: "' . $tag . '" in ' . static::class);
        }

        $this->body = $body;
        $this->bodies[] = $body;

        return $this->$method((object)$attrs);
    }

    public function __call(string $tag, array $args)
    {
        $this->callTag($tag, $args[0], $args[1]);
    }

    protected function requireAttributes(object $attrs, array $name)
    {
        $errors = [];
        foreach ($name as $n) {
            if (isset($attrs->$n) === false) {
                $errors[] = $n;
            }
        }
        if (count($errors) > 0) {
            throw new \ErrorException('The current tag "' . $this->currentTag . '" requires the attribute(s): ' . join(', ', $errors));
        }
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function isPreprocess(): bool
    {
        return $this->preProcess;
    }

}