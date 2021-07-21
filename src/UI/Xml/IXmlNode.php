<?php
namespace Pecee\UI\Xml;

interface IXmlNode
{

    public function __toString(): string;

    /**
     * @param XmlElement $parent
     */
    public function setParent($parent);

    /**
     * @return XmlElement
     */
    public function getParent();

}