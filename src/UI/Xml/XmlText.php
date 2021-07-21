<?php
namespace Pecee\UI\Xml;

class XmlText implements IXmlNode
{

    protected $parent;
    protected $text = '';

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function __toString(): string
    {
        return $this->text;
    }

    public function toXml()
    {
        return $this->text;
    }

}