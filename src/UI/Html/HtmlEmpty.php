<?php

namespace Pecee\UI\Html;

class HtmlEmpty extends Html
{

    public function __construct()
    {
        parent::__construct($tag = '');
    }

    public function render()
    {
        return '';
    }

}