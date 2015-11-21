<?php
namespace Pecee\UI\Form;

use Pecee\Boolean;
use Pecee\Dataset\Dataset;
use Pecee\Http\Input\Input;
use Pecee\Str;
use Pecee\UI\Html\HtmlCheckbox;
use Pecee\UI\Html\HtmlForm;
use Pecee\UI\Html\HtmlInput;
use Pecee\UI\Html\HtmlLabel;
use Pecee\UI\Html\HtmlSelect;
use Pecee\UI\Html\HtmlSelectOption;
use Pecee\UI\Html\HtmlTextarea;

class Form {
    const FORM_ENCTYPE_FORM_DATA = 'multipart/form-data';

    protected $input;
    protected $name;
    protected $indexes;

    public function __construct(Input $input) {
        $this->input = $input;
        $this->indexes = array();
    }

    protected function getValue($name, $defaultValue = null) {

        $method = request()->getMethod();

        if($method !== 'get') {
            if($this->input->post->get($name) !== null && count($_POST)) {
                return $this->input->post->get($name)->getValue();
            }

            if(strpos($name, '[]') !== false) {
                $index = $this->indexes[$name];
                $newName = substr($name, 0, strpos($name, '[]'));
                if(isset($_POST[$newName][$index])) {
                    return $_POST[$newName][$index];
                }
            }
        }

        if($this->input->get->get($name) !== null && count($_GET)) {
            return $this->input->get->get($name)->getValue();
        }

        return $defaultValue;
    }

    /**
     * Starts new form
     * @param string $name
     * @param string $method
     * @param string $action
     * @param string $enctype
     * @return \Pecee\UI\Html\HtmlForm
     */
    public function start($name = null, $method = 'post', $action = null, $enctype = 'multipart/form-data') {
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
    public function input($name, $type, $value = null, $saveValue = true) {
        if(is_null($value) && $this->getValue($name) || $saveValue && request()->getMethod() !== 'get') {
            $value = $this->getValue($name);
        }
        $element = new HtmlInput($name, $type, Str::htmlEntities($value));
        // Added: if the input is an radio, then save the god damn post value :-D...
        if(strtolower($type) == 'radio' && $this->getValue($name) && $value == $this->getValue($name) ) {
            $element->addAttribute('checked', 'checked');
        }
        return $element;
    }

    /**
     * Make new captcha element
     * @param string $name
     * @return \Pecee\UI\Form\FormCaptcha
     */
    public function captcha( $name ) {
        return new FormCaptcha($name);
    }

    /**
     * Creates new checkbox input element
     * @param string $name
     * @param bool $value
     * @param bool $saveValue
     * @param bool $defaultValue
     * @return \Pecee\UI\Html\HtmlCheckbox
     */
    public function bool($name, $value = true, $saveValue = true, $defaultValue = true) {
        $element = new HtmlCheckbox($name, $value);
        if($saveValue) {
            $checked = Boolean::parse($this->getValue($name, $defaultValue));
            if($checked) {
                $element->addAttribute('checked', 'checked');
            }
        } else {
            if(Boolean::parse($value)) {
                $element->addAttribute('checked', 'checked');
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
        if(!is_null($data)) {
            if($data instanceof Dataset) {
                $arr=$data->getData();
                if(count($arr) > 0) {
                    foreach($data->getData() as $i) {
                        $val=(!isset($i['value'])) ? $i['name'] : $i['value'];
                        $selected=($saveValue && $this->getValue($name) == $val  || $this->getValue($name) == $val || !$this->getValue($name) && $value == $val || (isset($i['selected']) && $i['selected']) || !$saveValue && $value == $val);
                        $element->addOption(new HtmlSelectOption($i['name'], $val, $selected));
                    }
                }
            } elseif(is_array($data)) {
                foreach($data as $val => $key) {
                    $selected=($saveValue && $this->getValue($name) == $val  || $this->getValue($name) == $val || !$this->getValue($name) && $value == $val || (isset($i['selected']) && $i['selected']) || !$saveValue && $value == $val);
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
    public function textarea($name, $rows, $cols, $value = null, $saveValue = true) {
        if(!$value && $this->getValue($name) || $saveValue && request()->getMethod() !== 'get') {
            $value = $this->getValue($name);
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
     * Ends open form
     * @return string
     */
    public function end() {
        return '</form>';
    }

    public function setPrefixElements($bool) {
        $this->prefixElements = $bool;
    }
}