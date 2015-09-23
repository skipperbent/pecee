<?php

namespace Pecee\Widget\Debug;

use Pecee\Widget\Widget;

class WidgetDebug extends Widget {

    protected $stack;

    public function __construct(array $stack) {
        parent::__construct();

        $this->stack = $stack;
    }

    public function render() {
        $this->renderContent();
        $this->renderTemplate();
        return $this->_contentHtml;
    }


}