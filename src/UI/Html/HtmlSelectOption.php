<?php
namespace Pecee\UI\Html;

use Pecee\UI\Site;

class HtmlSelectOption extends Html {

	public function __construct($value, $text = null, $selected = false) {
		parent::__construct('option');

        $this->addAttribute('value', $value);

		if($selected === true) {

            if($this->docType === Site::DOCTYPE_HTML_5) {
                $this->addAttribute('selected', null);
            } else {
                $this->addAttribute('selected', 'selected');
            }
		}

		if($text !== null) {
            $this->addInnerHtml($text);
        }
	}

}