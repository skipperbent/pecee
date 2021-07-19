<?php
namespace Pecee\UI;

use Pecee\UI\Html\Html;

class Tablist
{

    public function __construct()
    {
        app()->site->addWrappedJs('pecee-tablist.js');
    }

    public function button($id, $value = null): Html
    {
        $btn = new Html('a');
        $btn->addAttribute('href', '#');
        $btn->addAttribute('class', 'pecee-tablist');
        $btn->addAttribute('data-id', $id);
        $btn->addInnerHtml($value);

        return $btn;
    }

    public function start($id, $visible = false): Html
    {
        $tab = new Html('div');
        $tab->setClosingType(Html::CLOSE_TYPE_NONE);
        $tab->addAttribute('class', 'pecee-tablist');
        $tab->addAttribute('data-id', $id);
        $tab->addAttribute('data-visible', ($visible) ? 'true' : 'false');

        return $tab;
    }

    public function end(): string
    {
        return '</div>';
    }
}