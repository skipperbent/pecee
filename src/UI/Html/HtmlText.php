<?php

namespace Pecee\UI\Html;

use Pecee\UI\Xml\XmlText;

class HtmlText extends XmlText implements IHtmlNode
{

    public function toHtml(): string
    {
        return $this->toXml();
    }

}