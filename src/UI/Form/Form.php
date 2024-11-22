<?php

namespace Pecee\UI\Form;

use Pecee\Boolean;
use Pecee\Dataset\Dataset;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\UI\Html\Html;
use Pecee\UI\Html\HtmlCheckbox;
use Pecee\UI\Html\HtmlForm;
use Pecee\UI\Html\HtmlInput;
use Pecee\UI\Html\HtmlSelect;
use Pecee\UI\Html\HtmlSelectOption;
use Pecee\UI\Html\HtmlTextarea;

class Form
{

    protected bool $enableCsrfToken = true;

    /**
     * Starts new form
     * @param string $name
     * @param string|null $method
     * @param string|null $action
     * @return \Pecee\UI\Html\HtmlForm
     */
    public function start(string $name, string $method = HtmlForm::METHOD_POST, ?string $action = null): HtmlForm
    {
        $form = new HtmlForm($name, $method, $action);
        // Add csrf token
        if ($this->enableCsrfToken === true && strtolower($method) !== 'get') {
            $form->addInnerHtml(new HtmlInput(BaseCsrfVerifier::POST_KEY, 'hidden', csrf_token()));
        }

        return $form;
    }

    /**
     * Creates new HTML input element
     * @param string $name
     * @param string $type
     * @param string $value
     * @param bool $saveValue
     * @return \Pecee\UI\Html\HtmlInput
     */
    public function input(string $name, string $type = 'text', ?string $value = null, bool $saveValue = true): HtmlInput
    {
        if ($saveValue && (($value === null && input()->exists($name) === true) || request()->getMethod() !== 'get')) {
            $value = (string)input($name);
        }

        return new HtmlInput($name, $type, $value);
    }

    /**
     * Create radio element
     *
     * @param string $name
     * @param string $value
     * @param bool $saveValue
     * @return HtmlInput
     */
    public function radio(string $name, string $value, bool $saveValue = true): HtmlInput
    {
        $element = new HtmlInput($name, 'radio', $value);

        $inputValue = input($name);

        if ($saveValue === true && $inputValue !== null && (string)$inputValue === (string)$value) {
            $element->checked(true);
        }

        return $element;
    }

    /**
     * Creates new checkbox input element
     * @param string $name
     * @param bool $value
     * @param bool $defaultValue
     * @param bool $saveValue
     * @return \Pecee\UI\Html\HtmlCheckbox
     */
    public function bool($name, bool $value = true, bool $defaultValue = null, bool $saveValue = true): HtmlCheckbox
    {
        $element = new HtmlCheckbox($name, ($defaultValue === null) ? '1' : (int)$defaultValue);
        if ($saveValue !== false) {
            // On Postback
            if (request()->getMethod() !== 'get') {
                $checked = Boolean::parse(input($name));
            } else {
                if ($defaultValue === null) {
                    $defaultValue = $value;
                } else {
                    $defaultValue = count($_GET) ? null : $defaultValue;
                }
                $checked = Boolean::parse(input($name, $defaultValue));
            }
            if ($checked) {
                $element->checked(true);
            }
        } else {
            if (Boolean::parse($value)) {
                $element->checked(true);
            }
        }

        return $element;
    }

    /**
     * Creates new label
     * @param string|null $inner
     * @param string|null $for
     * @return \Pecee\UI\Html\Html
     */
    public function label(?string $inner = null, ?string $for = null): Html
    {
        $label = new Html('label');

        if ($inner !== null) {
            $label->addInnerHtml($inner);
        }

        if ($for !== null) {
            $label->attr('for', $for);
        }

        return $label;
    }

    /**
     * Creates new HTML Select element
     * @param string $name
     * @param array|Dataset|null $data
     * @param string|null $value
     * @param bool $saveValue
     * @return \Pecee\UI\Html\HtmlSelect
     * @throws \InvalidArgumentException
     */
    public function selectStart(string $name, mixed $data = null, ?string $value = null, bool $saveValue = true): HtmlSelect
    {
        $element = new HtmlSelect($name);
        if ($data !== null) {

            if ($data instanceof Dataset) {
                $data = $data->toArray();
            }

            if (is_array($data) === true) {

                foreach ((array)$data as $key => $val) {
                    $selected = ((input($name) !== null && (string)input($name) === (string)$val) || (input()->exists($name) === false && (string)$value === (string)$val) || ($saveValue === false && (string)$value === (string)$val));
                    $element->addOption(new HtmlSelectOption($key, $val, $selected));
                }

            } else {
                throw new \InvalidArgumentException('Data must be either instance of Dataset or array.');
            }
        }

        return $element;
    }

    /**
     * Creates new textarea
     * @param string $name
     * @param int $rows
     * @param int $cols
     * @param string $value
     * @param bool $saveValue
     * @return \Pecee\UI\Html\HtmlTextarea
     */
    public function textarea(string $name, int $rows, int $cols, ?string $value = null, bool $saveValue = true): HtmlTextarea
    {
        if ($saveValue === true && (($value === null && input($name) !== null) || request()->getMethod() !== 'get')) {
            $value = (string)input($name);
        }

        return new HtmlTextarea($name, $rows, $cols, $value);
    }

    /**
     * Creates submit element
     * @param string $name
     * @param string $value
     * @return \Pecee\UI\Html\HtmlInput
     */
    public function submit(string $name, string $value): HtmlInput
    {
        return $this->input($name, 'submit', $value, false);
    }

    /**
     * Create button element
     * @param string $text
     * @param string|null $type
     * @param string|null $name
     * @param string|null $value
     * @return Html
     */
    public function button(string $text, ?string $type = null, ?string $name = null, ?string $value = null): Html
    {
        $el = (new Html('button'))->addInnerHtml($text);

        if ($type !== null) {
            $el->addAttribute('type', $type);
        }

        if ($name !== null) {
            $el->addAttribute('name', $name);
        }

        if ($value !== null) {
            $el->addAttribute('value', $value);
        }

        return $el;
    }

    /**
     * Ends open form
     * @return string
     */
    public function end(): string
    {
        return '</form>';
    }

    public function setEnableCsrfToken($value): void
    {
        $this->enableCsrfToken = $value;
    }

    public function isCsrfTokenEnabled(): bool
    {
        return $this->enableCsrfToken;
    }

}