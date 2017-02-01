<?php
namespace Pecee\UI\Html;

class HtmlCheckbox extends HtmlInput
{

    public function __construct($name, $value = null)
    {
        parent::__construct($name, 'checkbox', ($value === null) ? 1 : $value);
    }

}