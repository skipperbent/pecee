<?php
namespace Pecee\UI\Html;

use Pecee\Str;
use Pecee\UI\Site;

class HtmlInput extends Html {

	public function __construct($name, $type, $value = null) {

		parent::__construct('input');

        $this->closingType = self::CLOSE_TYPE_SELF;

		$this->type($type);
		$this->name($name);

		if($value !== null){
			$this->value(Str::htmlEntities($value));
		}

	}

	public function name($name) {
        return $this->attr('name', $name);
    }

    public function value($value) {
        return $this->attr('value', $value);
    }

    public function placeholder($text) {
        return $this->attr('placeholder', $text);
    }

	public function autoComplete($bool = false) {
		return $this->attr('autocomplete', (($bool === true) ? 'on' : 'off'));
	}

	public function readonly() {
        return $this->addInputAttribute('readonly');
    }

    public function disabled() {
        return $this->addInputAttribute('disabled');
    }

    public function autofocus() {
        return $this->addInputAttribute('autofocus');
    }

    public function required() {
        return $this->addInputAttribute('required');
    }

    public function multiple() {
        return $this->addInputAttribute('required');
    }

    public function maxLength($maxLength) {
        return $this->attr('maxlength', $maxLength);
    }

    public function size($size) {
        return $this->attr('size', $size);
    }

    public function type($type) {
        return $this->attr('type', $type);
    }

    public function pattern($pattern) {
        return $this->attr('pattern', $pattern);
    }

    public function checked($checked) {
        if($checked)  {
            $this->addInputAttribute('checked', $checked);
        } else {
            $this->removeAttribute('checked');
        }
    }

    public function addInputAttribute($name) {
        if($this->docType === Site::DOCTYPE_HTML_5) {
            $this->attr($name, null);
        } else {
            $this->attr($name, $name);
        }

        return $this;
    }

}