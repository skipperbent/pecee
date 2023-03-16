<?php

namespace Pecee\UI\Taglib;

class Taglib implements ITaglib
{

    public const TAG_PREFIX = 'tag';

    protected ?string $body = '';
    protected array $bodies = [];
    protected bool $preProcess;
    protected string $currentTag;

    public function __construct(bool $preProcess = false)
    {
        $this->preProcess = $preProcess;
    }

    public function callTag(string $tag, array $attrs, ?string $body): ?string
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

    public function __call($tag, $args): string
    {
        return $this->callTag($tag, $args[0], $args[1]);
    }

    protected function requireAttributes(\stdClass $attrs, array $names): void
    {
        $errors = [];
        foreach ($names as $name) {
            if (isset($attrs->$name) === false) {
                $errors[] = $name;
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