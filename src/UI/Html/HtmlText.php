<?php
namespace Pecee\UI\Html;
class HtmlText extends \Pecee\UI\Xml\XmlText implements \Pecee\UI\Html\IHtmlNode {
	public function toHtml() {
		return $this->toXml();
	}
}