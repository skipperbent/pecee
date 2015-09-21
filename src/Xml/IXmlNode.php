<?php
namespace Pecee\Xml;
interface IXmlNode {
	public function __toString();
	/**
	 * @param XmlElement $parent
	*/
	public function setParent($parent);
	/**
	 * @return XmlElement
	*/
	public function getParent();
}