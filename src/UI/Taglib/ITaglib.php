<?php

namespace Pecee\UI\Taglib;

interface ITaglib
{

    public function callTag($tag, array $attrs, $body = '');

}