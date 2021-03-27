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

    protected $enableCsrfToken = true;

    /**
     * Starts new form
     * @param string $name
     * @param string|null $method
     * @param string|null $action
     * @return \Pecee\UI\Html\HtmlForm
     */
    public function start($name, $method = HtmlForm::METHOD_POST, $action = null)
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
    public function input($name, $type = 'text', $value = null, $saveValue = true)
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
    public function radio($name, $value, $saveValue = true)
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
    public function bool($name, $value = true, $defaultValue = null, $saveValue = true)
    {
        $element = new HtmlCheckbox($name, ($defaultValue === null) ? '1' : (int)$defaultValue);
        if ($saveValue !== false) {
            if ($defaultValue === null) {
                $defaultValue = $value;
            } else {
                $defaultValue = count($_GET) ? null : $defaultValue;
            }
            $checked = Boolean::parse(input($name, $defaultValue));
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
    public function label($inner, $for = null)
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
     * @throws \InvalidArgumentException
     * @return \Pecee\UI\Html\HtmlSelect
     */
    public function selectStart($name, $data = null, $value = null, $saveValue = true)
    {
        $element = new HtmlSelect($name);
        if ($data !== null) {
            if ($data instanceof Dataset) {

                foreach ($data->getData() as $item) {
                    $val = isset($item['value']) ? $item['value'] : $item['name'];
                    $selected = ((input($name) !== null && (string)input($name) === (string)$val) || (input()->exists($name) === false && (string)$value === (string)$val) || (isset($item['selected']) && $item['selected']) || ($saveValue === false && (string)$value === (string)$val));
                    $element->addOption(new HtmlSelectOption($val, $item['name'], $selected));
                }

            } elseif (is_array($data) === true) {

                foreach ((array)$data as $val => $key) {
                    $selected = ((input($name) !== null && (string)input($name) === (string)$val) || (input()->exists($name) === false && (string)$value === (string)$val) || ($saveValue === false && (string)$value === (string)$val));
                    $element->addOption(new HtmlSelectOption($val, $key, $selected));
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
    public function textarea($name, $rows, $cols, $value = null, $saveValue = true)
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
    public function submit($name, $value)
    {
        return $this->input($name, 'submit', $value);
    }

    /**
     * Create button element
     * @param string $text
     * @param string|null $type
     * @param string|null $name
     * @param string|null $value
     * @return Html
     */
    public function button($text, $type = null, $name = null, $value = null)
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
    public function end()
    {
        return '</form>';
    }

    public function setEnableCsrfToken($value)
    {
        $this->enableCsrfToken = $value;
    }

    public function isCsrfTokenEnabled()
    {
        return $this->enableCsrfToken;
    }

}