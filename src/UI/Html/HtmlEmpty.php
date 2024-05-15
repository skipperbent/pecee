<?php

namespace Pecee\UI\Html;

class HtmlEmpty extends Html
{

    public function __construct()
    {
        parent::__construct('');
    }

    public function render(): string
    {
        return '';
    }

}