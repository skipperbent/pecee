<?php

namespace Pecee\Widget\Debug;

use Pecee\Widget\Widget;

class WidgetDebug extends Widget
{
    protected array $stack;
    protected ?string $_template = '';

    public function __construct(array $stack)
    {
        parent::__construct();

        $this->getSite()->addCss('css/pecee-debug.css', 'debug');
        $this->getSite()->addJs('js/pecee-debug.js', 'debug');

        $this->stack = $stack;
    }

    protected function getTemplatePath(): string
    {
        $path = explode('\\', static::class);
        $path = array_slice($path, 2);

        return dirname(__DIR__, 3) . '/views/content/' . implode(DIRECTORY_SEPARATOR, $path) . '.php';
    }

}