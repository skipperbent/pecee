<?php
namespace Pecee\Widget;

use Pecee\Base;
use Pecee\Debug;
use Pecee\UI\Form\Form;
use Pecee\UI\Form\FormMessage;
use Pecee\UI\Html\HtmlLink;
use Pecee\UI\Html\HtmlMeta;
use Pecee\UI\Html\HtmlScript;

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
        $this->jsWrapRoute = url('pecee.js.wrap');
        $this->cssWrapRoute = url('pecee.css.wrap');
        $this->form = new Form($this->_input);
    }

    /**
     * Calculates template path from given Widget name.
     *
     * @return string
     */
    protected function getTemplatePath() {
        $path = explode('\\', get_class($this));
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
            return join('', $output);
        }
        return '';
    }

    /**
     * @param bool $includeJs
     * @param bool $includeCss
     * @return string
     */
    public function printHeader($includeJs = true, $includeCss = true) {

        $enc = new HtmlMeta('text/html; charset='.$this->_site->getCharset());
        $enc->addAttribute('http-equiv', 'Content-Type');
        $o = array($enc);

        if($this->_site->getTitle())  {
            $o[] = '<title>' . $this->_site->getTitle() . '</title>';
        }

        if($this->_site->getDescription()) {
            $this->_site->addMeta('description', $this->_site->getDescription());
        }
        if(count($this->_site->getKeywords())) {
            $this->_site->addMeta('keywords', join(', ', $this->_site->getKeywords()));
        }

        if($includeCss) {
            $o[] = $this->printCss();
        }

        if($includeJs) {
            $o[] = $this->printJs();
        }

        if(count($this->_site->getHeader())) {
            $header = $this->_site->getHeader();
            $o[] = join('', $header);
        }

        return join('', $o);
    }

    public function printCss() {
        $o = array();
        if($this->_site->getCssFilesWrapped()) {

            $getParams = array();

            if(env('DEBUG', false)) {
                $getParams = ['_' => time()];
            }

            $url = url($this->cssWrapRoute, null, array_merge(['files' => join($this->_site->getCssFilesWrapped(), ',')], $getParams));
            $o[] = new HtmlLink($url);
        }

        $css = $this->_site->getCss();
        if(count($css)) {
            foreach($css as $c) {
                $o[] = $c;
            }
        }
        return join('', $o);
    }

    public function printJs() {
        $o = array();
        if($this->_site->getJsFilesWrapped()) {

            $getParams = array();

            if(env('DEBUG', false)) {
                $getParams = ['_' => time()];
            }

            $url = url($this->jsWrapRoute, null, array_merge(['files' => join($this->_site->getJsFilesWrapped(), ',')], $getParams));
            $o[] = new HtmlScript($url);
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
        if($widget->getTemplate() === 'Template\Default.php') {
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
        Debug::getInstance()->add('END ' . get_class($this));
        return $this->_contentHtml;
    }

    protected function renderContent() {
        if($this->_contentHtml === null && $this->_contentTemplate !== null) {
            ob_start();
            include $this->_contentTemplate;
            $this->_contentHtml = ob_get_contents();
            ob_end_clean();
        }
    }

    protected function renderTemplate() {
        if($this->_template !== '') {
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