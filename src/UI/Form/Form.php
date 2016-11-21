<?php
namespace Pecee\UI\Form;

use Pecee\Boolean;
use Pecee\Dataset\Dataset;
use Pecee\UI\Html\Html;
use Pecee\UI\Html\HtmlCheckbox;
use Pecee\UI\Html\HtmlForm;
use Pecee\UI\Html\HtmlInput;
use Pecee\UI\Html\HtmlLabel;
use Pecee\UI\Html\HtmlSelect;
use Pecee\UI\Html\HtmlSelectOption;
use Pecee\UI\Html\HtmlTextarea;

class Form {

    protected $name;

    /**
     * Starts new form
     * @param string $name
     * @param string|null $method
     * @param string|null $action
     * @param string|null $enctype
     * @return \Pecee\UI\Html\HtmlForm
     */
    public function start($name, $method = HtmlForm::METHOD_POST, $action = null, $enctype = HtmlForm::ENCTYPE_APPLICATION_URLENCODED) {
        $this->name = $name;
        return new HtmlForm($name, $method, $action, $enctype);
    }

    /**
     * Creates new HTML input element
     * @param string $name
     * @param string $type
     * @param string $value
     * @param bool $saveValue
     * @return \Pecee\UI\Html\HtmlInput
     */
    public function input($name, $type = 'text', $value = null, $saveValue = true) {
        if($saveValue && ($value === null && input()->exists($name) || request()->getMethod() !== 'get')) {
            $value = input()->get($name);
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
    public function radio($name, $value, $saveValue = true) {
        $element = new HtmlInput($name, 'radio', $value);

        if($saveValue && input()->get($name) !== null && input()->get($name) == $value) {
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
    public function bool($name, $value = true, $defaultValue = null, $saveValue = true) {
        $element = new HtmlCheckbox($name, ($defaultValue === null) ? '1' : $defaultValue);
        if($saveValue !== false) {
            if($defaultValue === null) {
                $defaultValue = $value;
            } else {
                $defaultValue = (count($_GET)) ? null : $defaultValue;
            }
            $checked = Boolean::parse(input()->get($name, $defaultValue));
            if($checked) {
                $element->checked(true);
            }
        } else {
            if(Boolean::parse($value)) {
                $element->checked(true);
            }
        }
        return $element;
    }

    /**
     * Creates new label
     * @param string $value
     * @param string $for
     * @return \Pecee\UI\Html\HtmlLabel
     */
    public function label($value, $for=null) {
        return new HtmlLabel($value, $for);
    }

    /**
     * Creates new HTML Select element
     * @param string $name
     * @param Dataset $data
     * @param string|null $value
     * @param bool $saveValue
     * @return \Pecee\UI\Html\HtmlSelect
     */
    public function selectStart($name, $data = null, $value = null, $saveValue = true) {
        $element = new HtmlSelect($name);
        if($data !== null) {
            if($data instanceof Dataset) {

                foreach($data->getData() as $item) {
                    $val = isset($item['value']) ? $item['value'] : $item['name'];
                    $selected = (input()->get($name) !== null && input()->get($name) == $val || !input()->exists($name) && $value == $val || (isset($item['selected']) && $item['selected']) || !$saveValue && $value == $val);
                    $element->addOption(new HtmlSelectOption($val, $item['name'], $selected));
                }

            } elseif(is_array($data)) {

                foreach($data as $val => $key) {
                    $selected = (input()->get($name) !== null && input()->get($name) == $val || !input()->exists($name) && $value == $val || !$saveValue && $value == $val);
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
    public function textarea($name, $rows, $cols, $value = null, $saveValue = true) {
        if($saveValue && (!$value && input()->get($name) || request()->getMethod() !== 'get')) {
            $value = input()->get($name);
        }
        return new HtmlTextarea($name, $rows, $cols, $value);
    }

    /**
     * Creates submit element
     * @param string $name
     * @param string $value
     * @return \Pecee\UI\Html\HtmlInput
     */
    public function submit($name, $value) {
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
    public function button($text, $type = null, $name = null, $value = null) {
        $el = new Html('button');

        $el->addInnerHtml($text);

        if($type !== null) {
            $el->addAttribute('type', $type);
        }

        if($name !== null) {
            $el->addAttribute('name', $name);
        }

        if($value !== null) {
            $el->addAttribute('value', $value);
        }

        $el->setClosingType(Html::CLOSE_TYPE_TAG);
        return $el;
    }

    /**
     * Ends open form
     * @return string
     */
    public function end() {
        return '</form>';
    }

}