<?php

namespace Pecee\UI\Html;

class HtmlEmpty extends Html
{

    public function __construct($name = '', $type = '', $value = null)
    {
        parent::__construct('input');
    }

    public function render(): string
    {
        return '';
    }

}