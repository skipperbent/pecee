<?php

namespace Pecee\UI\Html;

class HtmlForm extends Html
{

    public const ENCTYPE_APPLICATION_URLENCODED = 'application/x-www-form-urlencoded';
    public const ENCTYPE_FORM_DATA = 'multipart/form-data';
    public const ENCTYPE_TEXT_PLAIN = 'text/plain';

    public const METHOD_POST = 'post';
    public const METHOD_GET = 'get';

    public function __construct(string $name, string $method = self::METHOD_POST, ?string $action = null, string $encoding = self::ENCTYPE_APPLICATION_URLENCODED)
    {
        parent::__construct('form');

        $this->closingType = static::CLOSE_TYPE_NONE;

        $this->name($name);
        $this->enctype($encoding);
        $this->method($method);
        $this->action($action ?? url());
    }

    /**
     * @param string $name
     * @return static
     */
    public function name(string $name): self
    {
        return $this->addAttribute('name', $name);
    }

    /**
     * @param string $method
     * @return static
     */
    public function method(string $method): self
    {
        if ($method !== static::METHOD_GET) {
            return $this->addAttribute('method', $method);
        }

        return $this;
    }

    /**
     * @param string $enctype
     * @return static
     */
    public function enctype(string $enctype): self
    {
        if ($enctype !== static::ENCTYPE_APPLICATION_URLENCODED) {
            return $this->addAttribute('enctype', $enctype);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function fileUpload(): self
    {
        return $this->enctype(static::ENCTYPE_FORM_DATA);
    }

    /**
     * @param string $action
     * @return static
     */
    public function action(string $action): self
    {
        return $this->addAttribute('action', $action);
    }

}