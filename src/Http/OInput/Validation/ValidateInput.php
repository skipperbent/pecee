<?php
namespace Pecee\Http\OInput\Validation;

abstract class ValidateInput implements IValidateInput {

	protected $name;
	protected $value;
	protected $index;
	protected $form;
	protected $placement;
	
	abstract public function validate();

	abstract public function getErrorMessage();
	
	public function setName($name) {
		$this->name=$name;
	}

	public function setValue($value) {
		$this->value=$value;
	}

	public function setIndex($index) {
		$this->index=$index;
	}

	public function getIndex() {
		return $this->index;
	}

	public function getValue() {
		return $this->value;
	}

	public function getName() {
		return $this->name;
	}

	public function getForm() {
		return $this->form;
	}

	public function setForm($form) {
		$this->form = $form;
	}

    /**
     * @return string|null
     */
    public function getPlacement() {
        return $this->placement;
    }

    /**
     * @param string|null $placement
     */
    public function setPlacement($placement) {
        $this->placement = $placement;
    }

}