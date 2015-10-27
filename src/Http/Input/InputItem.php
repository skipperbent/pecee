<?php
namespace Pecee\Http\Input;

use Pecee\Http\Input\Validation\IValidateInput;
use Pecee\Http\Input\Validation\ValidateInput;

class InputItem implements IInputItem {

    protected $validationErrors = array();
    protected $validations = array();
    protected $index;
    protected $name;
    protected $value;
    protected $form;

    public function __construct($index, $value) {
        $this->validations = array();
        $this->index = $index;
        $this->value = $value;

        $index = $this->index;

        if(strpos($index, '_') !== false) {
            $this->form = substr($index, 0, strpos($index, '_'));
            $index = substr($index, strlen($this->form)+1);
        }

        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', $index));
    }

    public function validates() {
        if(count($this->validations)) {
            /* @var $validation ValidateInput */
            foreach($this->validations as $validation) {
                if(!$validation->validate()) {
                    $this->validationErrors[] = $validation;
                }
            }
        }

        return (count($this->validationErrors) === 0);
    }

    public function addValidation($validation) {
        if(is_array($validation)) {
            $this->validations = array();

            foreach($validation as $v) {

                if(!($v instanceof IValidateInput)) {
                    throw new \ErrorException('Validation type must be an instance of ValidateInput - type given: ' . get_class($v));
                }

                $v->setIndex($this->index);
                $v->setName($this->name);
                $v->setValue($this->value);
                $v->setForm($this->form);
                $this->validations[] = $v;
            }

            return;

        }

        if(!($validation instanceof IValidateInput)) {
            throw new \ErrorException('Validation type must be an instance of ValidateInput - type given: ' . get_class($v));
        }

        $validation->setIndex($this->index);
        $validation->setName($this->name);
        $validation->setValue($this->value);
        $validation->setForm($this->form);

        $this->validations = array($validation);
    }

    /**
     * @return array
     */
    public function getName() {
        return Str::htmlEntitiesDecode($this->name);
    }

    /**
     * @return array
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @return array
     */
    public function getValidations() {
        return $this->validations;
    }

    /**
     * @return string
     */
    public function getIndex($removeFormName = false) {
        if($removeFormName && $this->form) {
            return substr($this->index, strlen($this->form)+1);
        }
        return $this->index;
    }

    /**
     * @return string
     */
    public function getForm() {
        return $this->form;
    }

    /**
     * @return array
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }

}