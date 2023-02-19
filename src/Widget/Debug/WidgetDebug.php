<?php
namespace Pecee\Widget\Debug;

use Pecee\Widget\Widget;

class WidgetDebug extends Widget
{
    protected array $stack;

    public function __construct(array $stack)
    {
        $this->getSite()->addWrappedCss('css/pecee-debug.css', 'debug');
        $this->getSite()->addWrappedJs('js/pecee-debug.js', 'debug');

        $this->setTemplate(null);
        $this->stack = $stack;
    }

    protected function getTemplatePath(): string
    {
        $path = explode('\\', static::class);
        $path = \array_slice($path, 2);

        return env('framework_path') . '/views/content/' . implode(DIRECTORY_SEPARATOR, $path) . '.php';
    }

    protected function formatTime(float $microTime): string
    {
        return number_format($microTime, 10);
    }

}