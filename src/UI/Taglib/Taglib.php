<?php

namespace Pecee\UI\Taglib;

use Closure;
use ErrorException;
use InvalidArgumentException;

class Taglib implements ITaglib
{

    public const TAG_PREFIX = 'tag';

    protected ?string $body = null;
    protected array $bodies = [];
    protected bool $preProcess = false;
    protected string $currentTag = '';
    protected ?Closure $renderCallback = null;

    public function __construct(bool $preProcess = false)
    {
        $this->preProcess = $preProcess;
    }

    /**
     * Callback to provide context when rendering inline-php
     *
     * @param string $content
     * @return string
     */
    protected function renderPhp(string $content): string
    {
        if ($this->renderCallback !== null) {
            return call_user_func($this->renderCallback, $content);
        }

        ob_start();
        eval('?>' . $content);
        return ob_get_clean();
    }

    public function setRenderCallback(Closure $callback): self
    {
        $this->renderCallback = $callback;

        return $this;
    }

    public function callTag(string $tag, array $attrs, ?string $body = null)
    {
        $this->currentTag = $tag;
        $method = static::TAG_PREFIX . ucfirst($tag);

        if (method_exists($this, $method) === false) {
            throw new InvalidArgumentException('Unknown tag: "' . $tag . '" in ' . static::class);
        }

        $this->body = $body;
        $this->bodies[] = $body;

        return $this->$method((object)$attrs);
    }

    public function __call(string $tag, array $args)
    {
        $this->callTag($tag, $args[0] ?? [], $args[1] ?? null);
    }

    protected function requireAttributes(object $attrs, array $name): void
    {
        $errors = [];
        foreach ($name as $n) {
            if (isset($attrs->$n) === false) {
                $errors[] = $n;
            }
        }
        if (count($errors) > 0) {
            throw new ErrorException('The current tag "' . $this->currentTag . '" requires the attribute(s): ' . implode(', ', $errors));
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