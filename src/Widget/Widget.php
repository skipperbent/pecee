<?php

namespace Pecee\Widget;

use Pecee\Base;
use Pecee\UI\Form\Form;
use Pecee\UI\Html\Html;
use Pecee\UI\Site;

abstract class Widget extends Base
{
    protected ?string $_template = null;
    protected ?string $_contentTemplate = null;
    protected ?string $_contentHtml = null;

    protected function onLoad(): void
    {

    }

    protected function onPostBack(): void
    {

    }

    protected function onRender(string $html): string
    {
        return $this->_contentHtml;
    }

    /**
     * Calculates template path from given Widget name.
     * @return string
     */
    protected function getTemplatePath(): string
    {
        $path = substr(static::class, strpos(static::class, 'Widget') + 7);

        return 'views/content/' . str_replace('\\', DIRECTORY_SEPARATOR, $path) . '.php';
    }

    public function showMessages(string $type, ?string $placement = null): string
    {
        $placement = $placement ?? $this->defaultMessagePlacement;

        $errors = $this->getMessages($type, $placement);

        if (\count($errors) > 0) {
            $output = (new Html('div'))->addClass('alert')->addClass(sprintf('alert-%s', $type));

            $msg = [];
            foreach ($errors as $error) {
                $msg[] = $error->getMessage();
            }

            $output->addInnerHtml(implode('<br>', $msg));

            return $output;
        }

        return '';
    }

    public function showFlash(?string $placement = null): string
    {
        $o = $this->showMessages($this->errorType, $placement);
        $o .= $this->showMessages('warning', $placement);
        $o .= $this->showMessages('info', $placement);
        $o .= $this->showMessages('success', $placement);

        return $o;
    }

    public function renderValidationFor(string $name): string
    {
        $validation = parent::getValidationFor($name);

        if ($validation !== null) {
            $span = new Html('div');
            $span->addClass('text-danger small');
            $span->addInnerHtml($validation);

            return $span;
        }

        return '';
    }

    /**
     * @return string
     */
    public function printMeta(): string
    {
        $output = '';

        if ($this->getSite()->getDescription() !== null) {
            $this->getSite()->addMeta(['content' => $this->getSite()->getDescription(), 'name' => 'description']);
        }

        if (\count($this->getSite()->getKeywords()) > 0) {
            $this->getSite()->addMeta(['content' => implode(', ', $this->getSite()->getKeywords()), 'name' => 'keywords']);
        }

        if (\count($this->getSite()->getHeader()) > 0) {
            $header = $this->getSite()->getHeader();
            $output .= implode('', $header);
        }

        return $output;
    }

    public function printCss(string $section = Site::SECTION_DEFAULT): string
    {
        $output = '';

        if (\count($this->getSite()->getCssFilesWrapped($section)) > 0) {
            $css = url(app()->getCssWrapRouteName(), null, ['files' => implode(',', $this->getSite()->getCssFilesWrapped($section))]);
            $output .= (new Html('link'))->setClosingType(Html::CLOSE_TYPE_NONE)->attr('href', $css)->attr('rel', 'stylesheet');
        }

        foreach ($this->getSite()->getCss($section) as $css) {
            $output .= (new Html('link'))
                ->setClosingType(Html::CLOSE_TYPE_NONE)
                ->attr('href', $css)
                ->attr('rel', 'stylesheet');
        }

        return $output;
    }

    public function printJs(string $section = Site::SECTION_DEFAULT): string
    {
        $output = '';

        if (\count($this->getSite()->getJsFilesWrapped($section)) > 0) {
            $js = url(app()->getJsWrapRouteName(), null, ['files' => implode(',', $this->getSite()->getJsFilesWrapped($section))]);
            $output .= (new Html('script'))->attr('src', $js);
        }

        foreach ((array)$this->getSite()->getJs($section) as $js) {
            $output .= (new Html('script'))->attr('src', $js);
        }

        return $output;
    }

    protected function getTemplate(): ?string
    {
        return $this->_template;
    }

    protected function setTemplate(?string $path, bool $relative = true): void
    {
        $this->_template = (($relative === true && trim((string)$path) !== '') ? 'views' . DIRECTORY_SEPARATOR : '') . $path;
    }

    protected function setContentTemplate(?string $template): void
    {
        $this->_contentTemplate = $template;
    }

    protected function getContentTemplate(): ?string
    {
        return $this->_contentTemplate;
    }

    protected function setContentHtml(string $html): void
    {
        $this->_contentHtml = $html;
    }

    protected function getContentHtml(): ?string
    {
        return $this->_contentHtml;
    }

    /**
     * Creates form element
     * @return Form
     */
    public function form(): Form
    {
        return new Form();
    }

    /**
     * Include snippet from the content/snippet directory
     * by filling the path to the desired snippet.
     *
     * @param string $file
     */
    public function snippet(string $file): void
    {
        require 'views/snippets/' . $file;
    }

    /**
     * Include widget on page.
     *
     * @param \Pecee\Widget\Widget $widget
     * @return string
     */
    public function widget(Widget $widget): string
    {
        if ($widget->getTemplate() === $this->getTemplate()) {
            $widget->setTemplate(null);
        }

        return (string)$widget->render();
    }

    public function __toString(): string
    {
        return (string)$this->render();
    }

    /**
     * @return string
     */
    public function render(): ?string
    {
        // Trigger onLoad event
        $this->onLoad();

        // Trigger postback event
        if (request()->getMethod() === 'post') {
            $this->onPostBack();
        }

        if ($this->_template === null) {
            $this->setTemplate('Default.php');
        }

        if ($this->_contentTemplate === null) {
            $this->setContentTemplate($this->getTemplatePath());
        }

        $this->setInputValues();

        $this->renderContent();
        $this->renderTemplate();

        debug('widget', 'END %s:', static::class);

        return $this->onRender($this->_contentHtml);

    }

    protected function renderContent(): void
    {
        debug('widget', 'START: rendering content-template: %s', $this->_contentTemplate);

        if ($this->_contentHtml === null && $this->_contentTemplate !== null) {
            ob_start();
            include $this->_contentTemplate;
            $this->_contentHtml = ob_get_clean();
        }

        debug('widget', 'END: rendering content-template: %s', $this->_contentTemplate);
    }

    protected function renderTemplate(): void
    {
        debug('widget', 'START: rendering template: %s', $this->_template);

        if ($this->_template !== '') {
            ob_start();
            include $this->_template;
            $this->_contentHtml = ob_get_clean();
        }

        debug('widget', 'END: rendering template %s', $this->_template);
    }

}