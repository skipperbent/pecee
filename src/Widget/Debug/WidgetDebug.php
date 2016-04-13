<?php

namespace Pecee\Widget\Debug;

use Pecee\Widget\Widget;

class WidgetDebug extends Widget {

    protected $stack;

    public function __construct(array $stack) {
        parent::__construct();

        $this->setTemplate(null);

        $this->stack = $stack;
    }

    protected function getTemplatePath() {
        $path=explode('\\', get_class($this));
        $path = array_slice($path, 2);
        return $_ENV['framework_path'] . '/views/content/' . join(DIRECTORY_SEPARATOR, $path) . '.php';
    }

    public function render() {
        $this->renderContent();
        $this->renderTemplate();
        return $this->_contentHtml;
    }


}