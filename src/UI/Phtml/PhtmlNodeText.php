<?php
namespace Pecee\UI\Phtml;

use Pecee\UI\Html\HtmlText;

class PhtmlNodeText extends HtmlText
{
    public function toPHP(): string
    {
        return $this->__toString();
    }
}