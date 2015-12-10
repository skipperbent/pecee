<?php
namespace Pecee\UI\Html;

class HtmlCheckbox extends HtmlInput {

	public function __construct($name, $value = null) {
		$value = (is_null($value)) ? 1 : $value;
		parent::__construct($name, 'checkbox', $value);
	}

    public function checked($bool) {
        if($bool)  {
            $this->attributes['checked'] = 'checked';
        } else {
            unset($this->attributes['checked']);
        }
        return $this;
    }

}