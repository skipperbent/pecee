<?php
namespace Pecee\Http\Input;

use Pecee\Http\Input\Validation\ValidateInput;
use Pecee\Str;

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
        }

        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', $this->index));
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

    public function addValidation($validation, $placement = null) {
        if(is_array($validation)) {
            $this->validations = array();

            foreach($validation as $v) {

                if(!($v instanceof ValidateInput)) {
                    throw new \ErrorException('Validation type must be an instance of ValidateInput - type given: ' . get_class($v));
                }

                $v->setIndex($this->index);

                // Only set name if it's not already set
                if($v->getName() === null) {
                    $v->setName($this->name);
                }

                $v->setValue($this->value);
                $v->setForm($this->form);
                $v->setPlacement($placement);
                $this->validations[] = $v;
            }

            return;

        }

        if(!($validation instanceof ValidateInput)) {
            throw new \ErrorException('Validation type must be an instance of ValidateInput - type given: ' . get_class($validation));
        }

        $validation->setIndex($this->index);

        // Only set name if it's not already set
        if($validation->getName() === null) {
            $validation->setName($this->name);
        }

        $validation->setValue($this->value);
        $validation->setForm($this->form);
        $validation->setPlacement($placement);

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
    public function getIndex() {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getForm() {
        return $this->form;
    }

    /**
     * Set input name
     * @param string $name
     * @return static $this
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }

}