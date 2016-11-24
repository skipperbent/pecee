<?php
namespace Pecee\Widget\Debug;

use Pecee\Widget\Widget;

class WidgetDebug extends Widget
{
	protected $stack;
	protected $group = null;

	public function __construct(array $stack)
	{
		parent::__construct();

		$this->getSite()->addWrappedCss('css/pecee-debug.css', 'debug');
		$this->getSite()->addWrappedJs('js/pecee-debug.js', 'debug');

		$this->setTemplate(null);
		$this->stack = $stack;
	}

	protected function getTemplatePath()
	{
		$path = explode('\\', get_class($this));
		$path = array_slice($path, 2);

		return $_ENV['framework_path'] . '/views/content/' . join(DIRECTORY_SEPARATOR, $path) . '.php';
	}

	/**
	 * Render debug
	 * @return string
	 */
	public function render()
	{
		$this->renderContent();
		$this->renderTemplate();

		return $this->_contentHtml;
	}

}