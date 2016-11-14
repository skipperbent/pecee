<?php
namespace Pecee\Widget;

use Pecee\Base;
use Pecee\UI\Form\Form;
use Pecee\UI\Html\HtmlLink;
use Pecee\UI\Html\HtmlMeta;
use Pecee\UI\Html\HtmlScript;
use Pecee\UI\Site;

abstract class Widget extends Base  {

    protected $jsWrapRoute;
    protected $cssWrapRoute;
    protected $_template;
    protected $_contentTemplate;
    protected $_contentHtml;
    protected $form;

    public function __construct() {

        parent::__construct();

        debug('START WIDGET: ' . static::class);
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
        $path = array_slice(explode('\\', static::class), 2);
        return 'Template' . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $path) . '.php';
    }

    public function showMessages($type, $form = null, $placement = null) {
        $placement = ($placement === null) ? $this->defaultMessagePlacement : $placement;
        if($this->hasMessages($type, $form, $placement)) {
            $o = sprintf('<div class="alert alert-%s">', $type);
            $msg = array();
            /* @var $error \Pecee\UI\Form\FormMessage */
            foreach($this->getMessages($type, $form, $placement) as $error) {
                $msg[] = sprintf('%s', $error->getMessage());
            }

            $o .= join('<br/>', $msg) . '</div>';
            return $o;
        }
        return '';
    }

    /**
     * @param bool $includeJs
     * @param bool $includeCss
     * @return string
     */
    public function printHeader($includeJs = true, $includeCss = true) {

        $enc = new HtmlMeta();
        $enc->addAttribute('charset', $this->getSite()->getCharset());

        $o = $enc;

        if($this->getSite()->getTitle())  {
            $o .= '<title>' . $this->getSite()->getTitle() . '</title>';
        }

        if($this->getSite()->getDescription()) {
            $this->getSite()->addMeta('description', $this->getSite()->getDescription());
        }
        if(count($this->getSite()->getKeywords())) {
            $this->getSite()->addMeta('keywords', join(', ', $this->getSite()->getKeywords()));
        }

        if($includeCss === true) {
            $o .= $this->printCss();
        }

        if($includeJs === true) {
            $o .= $this->printJs();
        }

        if(count($this->getSite()->getHeader())) {
            $header = $this->getSite()->getHeader();
            $o .= join('', $header);
        }

        return $o;
    }

    public function printCss($section = Site::SECTION_DEFAULT) {
        $o = '';
        if(count($this->getSite()->getCssFilesWrapped($section))) {

            $getParams = array();

            if(env('DEBUG', false)) {
                $getParams = ['_' => time()];
            }

            $url = url($this->cssWrapRoute, null, array_merge(['files' => join($this->getSite()->getCssFilesWrapped($section), ',')], $getParams));
            $o .= new HtmlLink($url);
        }

        foreach($this->getSite()->getCss($section) as $c) {
            $o .= $c;
        }
        return $o;
    }

    public function printJs($section = Site::SECTION_DEFAULT) {
        $o = '';
        if(count($this->getSite()->getJsFilesWrapped($section))) {

            $getParams = array();

            if(env('DEBUG', false)) {
                $getParams = ['_' => time()];
            }

            $url = url($this->jsWrapRoute, null, array_merge(['files' => join($this->getSite()->getJsFilesWrapped($section), ',')], $getParams));
            $o .= new HtmlScript($url);
        }

        foreach($this->getSite()->getJs($section) as $j) {
            $o .= $j;
        }
        return $o;
    }

    protected function getTemplate() {
        return $this->_template;
    }

    protected function setTemplate($path,$relative = true) {
        $this->_template = (($relative === true && trim($path) !== '') ? 'Template' . DIRECTORY_SEPARATOR : '') . $path;
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
        require('Template' . DIRECTORY_SEPARATOR . 'Snippet' . DIRECTORY_SEPARATOR . $file);
    }

    /**
     * Include widget on page.
     * @param \Pecee\Widget\Widget $widget
     */
    public function widget(Widget $widget) {
        if($widget->getTemplate() === 'Template'. DIRECTORY_SEPARATOR .'Default.php') {
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
        $this->_messages->clear();
        debug('END WIDGET: ' . static::class);
        return $this->_contentHtml;
    }

    protected function renderContent() {
        debug('START: rendering content-template: ' . $this->_contentTemplate);
        if($this->_contentHtml === null && $this->_contentTemplate !== null) {
            ob_start();
            include $this->_contentTemplate;
            $this->_contentHtml = ob_get_contents();
            ob_end_clean();
        }
        debug('END: rendering content-template: ' . $this->_contentTemplate);
    }

    protected function renderTemplate() {
        debug('START: rendering template: ' . $this->_template);
        if($this->_template !== '') {
            ob_start();
            include $this->_template;
            $this->_contentHtml = ob_get_contents();
            ob_end_clean();
        }
        debug('END: rendering template ' . $this->_template);
    }

    protected function setJsWrapRoute($route) {
        $this->jsWrapRoute = $route;
    }

    protected function setCssWrapRoute($route) {
        $this->cssWrapRoute = $route;
    }

}