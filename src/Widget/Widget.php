<?php
namespace Pecee\Widget;

use Pecee\Base;
use Pecee\UI\Form\Form;
use Pecee\UI\Html\HtmlLink;
use Pecee\UI\Html\HtmlMeta;
use Pecee\UI\Html\HtmlScript;
use Pecee\UI\Site;

abstract class Widget extends Base  {

    protected $_jsWrapRoute;
    protected $_cssWrapRoute;
    protected $_template;
    protected $_contentTemplate;
    protected $_contentHtml;
    protected $_form;

    public function __construct() {

        parent::__construct();

        debug('START WIDGET: ' . static::class);
        $this->setTemplate('Default.php');
        $this->setContentTemplate($this->getTemplatePath());
        $this->_jsWrapRoute = 'pecee.js.wrap';
        $this->_cssWrapRoute = 'pecee.css.wrap';
        $this->_form = new Form();
    }

    /**
     * Calculates template path from given Widget name.
     * @return string
     */
    protected function getTemplatePath() {
        $path = array_slice(explode('\\', static::class), 2);
        return 'views/content/' . join(DIRECTORY_SEPARATOR, $path) . '.php';
    }

    public function showMessages($type, $placement = null) {
        $placement = ($placement === null) ? $this->defaultMessagePlacement : $placement;

        if($this->hasMessages($type, $placement)) {
            $o = sprintf('<div class="alert alert-%s">', $type);

            $msg = array();
            /* @var $error \Pecee\UI\Form\FormMessage */
            foreach($this->getMessages($type, $placement) as $error) {
                $msg[] = $error->getMessage();
            }

            return $o . nl2br(join(chr(10), $msg), ($this->getSite()->getDocType() !== Site::DOCTYPE_HTML_5)) . '</div>';
        }

        return '';
    }

    /**
     * @return string
     */
    public function printHeader() {

        $meta = new HtmlMeta();
        $meta->addAttribute('charset', $this->getSite()->getCharset());

        $output = $meta;

        if($this->getSite()->getTitle())  {
            $output .= '<title>' . $this->getSite()->getTitle() . '</title>';
        }

        if($this->getSite()->getDescription()) {
            $this->getSite()->addMeta('description', $this->getSite()->getDescription());
        }
        if(count($this->getSite()->getKeywords())) {
            $this->getSite()->addMeta('keywords', join(', ', $this->getSite()->getKeywords()));
        }

        $output .= $this->printCss();
        $output .= $this->printJs();

        if(count($this->getSite()->getHeader())) {
            $header = $this->getSite()->getHeader();
            $output .= join('', $header);
        }

        return $output;
    }

    public function printCss($section = Site::SECTION_DEFAULT) {
        $output = '';

        if(count($this->getSite()->getCssFilesWrapped($section))) {
            $url = url($this->_cssWrapRoute, null, ['files' => join($this->getSite()->getCssFilesWrapped($section), ',')]);
            $output .= new HtmlLink($url);
        }

        foreach($this->getSite()->getCss($section) as $css) {
            $output .= new HtmlLink($css);
        }

        return $output;
    }

    public function printJs($section = Site::SECTION_DEFAULT) {
        $output = '';

        if(count($this->getSite()->getJsFilesWrapped($section))) {
            $url = url($this->_jsWrapRoute, null, ['files' => join($this->getSite()->getJsFilesWrapped($section), ',')]);
            $output .= new HtmlScript($url);
        }

        foreach($this->getSite()->getJs($section) as $js) {
            $output .= new HtmlScript($js);
        }

        return $output;
    }

    protected function getTemplate() {
        return $this->_template;
    }

    protected function setTemplate($path, $relative = true) {
        $this->_template = (($relative === true && trim($path) !== '') ? 'views' . DIRECTORY_SEPARATOR : '') . $path;
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
        return $this->_form;
    }

    /**
     * Include snippet from the content/snippet directory
     * by filling the path to the desired snippet.
     *
     * @param string $file
     */
    public function snippet($file) {
        require 'views/snippets/' . $file;
    }

    /**
     * Include widget on page.
     * @param \Pecee\Widget\Widget $widget
     */
    public function widget(Widget $widget) {
        if($widget->getTemplate() === $this->getTemplate()) {
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
        $this->_jsWrapRoute = $route;
    }

    protected function setCssWrapRoute($route) {
        $this->_cssWrapRoute = $route;
    }

}