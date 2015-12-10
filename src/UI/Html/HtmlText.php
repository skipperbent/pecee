<?php
namespace Pecee\UI\Html;

use Pecee\Xml\XmlText;

class HtmlText extends XmlText implements IHtmlNode {

	public function toHtml() {
		return $this->toXml();
	}

}