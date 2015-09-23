<?php
namespace Pecee\Widget;

use Pecee\Auth;
use Pecee\Base;
use Pecee\Debug;
use Pecee\String;
use Pecee\UI\Form\Form;
use Pecee\UI\Form\FormMessage;
use Pecee\UI\Html\HtmlLink;
use Pecee\UI\Html\HtmlMeta;
use Pecee\UI\Html\HtmlScript;
use Pecee\Url;

abstract class Widget extends Base  {
	protected $jsWrapRoute;
	protected $cssWrapRoute;
	protected $_template;
	protected $_contentTemplate;
	protected $_contentHtml;
	protected $form;

	public function __construct() {

		parent::__construct();

		Debug::getInstance()->add('START ' . get_class($this));
		$this->setTemplate('Default.php');
		$this->setContentTemplate($this->getTemplatePath());
		$this->jsWrapRoute = url('js','wrap');
		$this->cssWrapRoute = url('css','wrap');
	}

	/**
	 * Calculates template path from given Widget name.
	 *
	 * @return string
	 */
	protected function getTemplatePath() {
		$path=explode('\\', get_class($this));
		$path = array_slice($path, 2);
		return 'Template' . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $path) . '.php';
	}

	public function showMessages($type) {
		if($this->hasMessages($type)) {
			$output = array();
			$output[] = sprintf('<ul class="msg %s">', $type);
			/* @var $error FormMessage */
			foreach($this->getMessages($type) as $error) {
				 $output[] = sprintf('<li>%s</li>', $error->getMessage());
			}
			$output[] = '</ul>';
			return join($output, '');
		}
		return '';
	}

	/**
     * @param bool $includeCss
     * @param bool $includeJs
	 * @return string
	 */
	public function printHeader($includeCss=true, $includeJs=true) {

		$enc=new HtmlMeta('text/html; charset='.$this->_site->getCharset());
		$enc->addAttribute('http-equiv', 'Content-Type');
		$o=array($enc);

		if($this->_site->getTitle())  {
			$o[]='<title>' . $this->_site->getTitle() . '</title>';
		}

		if($this->_site->getDescription()) {
			$this->_site->addMeta('description', $this->_site->getDescription());
		}
		if(count($this->_site->getKeywords()) > 0) {
			$this->_site->addMeta('keywords', join(', ', $this->_site->getKeywords()));
		}

		$get=null;
		if($this->getSite()->hasAdminIp()) {
			$get=array();
			if($this->_site->getDebug()) {
				$get['__clearcache']='true';
			}
			if($this->_site->getDebug()) {
				$get['__debug']='true';
			}
		}

		if($includeCss) {
			$o[] = $this->printCss();
		}
		if($includeJs) {
			$o[] = $this->printJs();
		}
		if(count($this->_site->getHeader()) > 0) {
			$header = $this->_site->getHeader();
			$o[]=join(chr(10), $header);
		}
		return join('', $o);
	}

	protected function printCss() {
		$o = array();
		if($this->_site->getCssFilesWrapped()) {
			$get=null;
			if($this->getSite()->hasAdminIp()) {
				$get=array();
				if($this->_site->getDebug()) {
					$get['__clearcache']='true';
				}
				if($this->_site->getDebug()) {
					$get['__debug']='true';
				}
			}

			$p = $this->cssWrapRoute;
            $p .= join($this->_site->getCssFilesWrapped(), ',') . Url::getParamsSeperator($this->cssWrapRoute) . Url::arrayToParams($get);
			$o[] = new HtmlLink($p);
		}

		$css = $this->_site->getCss();
		if(count($css) > 0) {
			foreach($css as $c) {
				$o[] = $c;
			}
		}
		return join('',$o);
	}

	protected function printJs() {
		$o = array();
		if($this->_site->getJsFilesWrapped()) {
			$get=null;
			if($this->getSite()->hasAdminIp()) {
				$get=array();
				if($this->_site->getDebug()) {
					$get['__clearcache']='true';
				}
				if($this->_site->getDebug()) {
					$get['__debug']='true';
				}
			}

			$p = $this->jsWrapRoute;
            $p .= join($this->_site->getJsFilesWrapped(),',') . Url::getParamsSeperator($this->jsWrapRoute) . Url::arrayToParams($get);
			$o[] = new HtmlScript($p);
		}

		$js = $this->_site->getJs();
		if(count($js) > 0) {
			foreach($js as $j) {
				$o[] = $j;
			}
		}
		return join('', $o);
	}

	protected function getTemplate() {
		return $this->_template;
	}

	protected function setTemplate($path,$relative=true) {
		$this->_template = (($relative && !empty($path)) ? 'Template' . DIRECTORY_SEPARATOR : '') . $path;
	}

	protected function setContentTemplate($template) {
		$this->_contentTemplate = $template;
	}

	protected function getContentTemplate() {
		return $this->_contentTemplate;
	}

	protected function setContentHtml($html) {
		$this->_contentHtml = $html;
	}

	protected function getContentHtml() {
		return $this->_contentHtml;
	}

	/**
	 * Creates form element
	 * @return Form
	 */
	public function form() {
		return $this->form;
	}

	/**
	 * Include snippet from the content/snippet directory
	 * by filling the path to the desired snippet.
	 *
	 * @param string $file
	 */
	public function snippet($file) {
		require('Template'.DIRECTORY_SEPARATOR.'Snippet'.DIRECTORY_SEPARATOR.$file);
	}

	/**
	 * Include widget on page.
	 * @param \Pecee\Widget\Widget $widget
	 */
	public function widget(Widget $widget) {
		if($widget->getTemplate() == 'Template\Default.php') {
			$widget->setTemplate(null);
		}
		echo $widget;
	}

	public function __toString() {
		try {
			return $this->render();
		}catch(\Exception $e) {
			$this->setError($e->getMessage());
		}
        return '';
	}

	public function render()  {
		$this->renderContent();
		$this->renderTemplate();
		$output = String::getFirstOrDefault($this->_contentHtml, '');
		Debug::getInstance()->add('END ' . get_class($this));
		// Output debug info
		if($this->getSite()->getDebug() && String::getFirstOrDefault($this->_template, false) && $this->getSite()->hasAdminIp() && strtolower($this->getParam('__debug')) == 'true') {
            $output .= Debug::getInstance();
		}
		return $output;
	}

	protected function renderContent() {
		if(is_null($this->_contentHtml) && !is_null($this->_contentTemplate)) {
			ob_start();
			include $this->_contentTemplate;
			$this->_contentHtml = ob_get_contents();
			ob_end_clean();
		}
	}

	protected function renderTemplate() {
		if(String::getFirstOrDefault($this->_template, false)) {
			ob_start();
			include $this->_template;
			$this->_contentHtml = ob_get_contents();
			ob_end_clean();
		}
	}

	protected function setJsWrapRoute($route) {
		$this->jsWrapRoute = $route;
	}

	protected function setCssWrapRoute($route) {
		$this->cssWrapRoute = $route;
	}
}