<?php
namespace Pecee\UI\Html;

use Pecee\Str;

class HtmlTextarea extends Html {

	protected $value;

    public function __construct($name, $rows = null, $cols = null, $value = '') {

        parent::__construct('textarea');

		$this->value = Str::htmlEntities($value);
		$this->addAttribute('name', $name);

        if($rows !== null) {
            $this->rows($rows);
        }

        if($cols !== null) {
            $this->cols($cols);
        }

		$this->addInnerHtml($this->value);
	}

	public function getValue() {
		return $this->value;
	}

	public function placeholder($text) {
		$this->addAttribute('placeholder', $text);
		return $this;
	}

	public function wrap($wrap) {
        return $this->attr('wrap', $wrap);
    }

    public function rows($rows) {
        return $this->attr('rows', $rows);
    }

    public function cols($cols) {
        return $this->attr('cols', $cols);
    }

}