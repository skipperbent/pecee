<?php

namespace Pecee\UI\Taglib;

interface ITaglib
{
    public function callTag(string $tag, array $attrs, ?string $body = null);
}