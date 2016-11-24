<?php
namespace Pecee\UI\Xml;

class Xml
{
	public static function toXml($data, $parent = 'root')
	{

		if (!($parent instanceof IXmlNode)) {
			$parent = new XmlElement((string)$parent);
		}

		switch (true) {
			case is_array($data):
			case is_object($data):
				$parent->setAttribute('type', 'structure');
				foreach ($data as $key => $value) {
					if (is_int($key)) {
						$key = 'element';
					}
					$node = new XmlElement($key);
					$parent->addChild($node);
					self::toXml($value, $node);
				}
				break;
			case is_bool($data):
				$parent->setAttribute('type', 'boolean');
				$parent->addChild(new XmlText(($data) ? 'true' : 'false'));
				break;
			case is_int($data):
				$parent->setAttribute('type', 'integer');
				$parent->addChild(new XmlText($data));
				break;
			case is_string($data):
				$parent->setAttribute('type', 'string');
				$parent->addChild(new XmlText($data));
				break;
			case is_float($data):
				$parent->setAttribute('type', 'float');
				$parent->addChild(new XmlText($data));
				break;
			case is_double($data):
				$parent->setAttribute('type', 'double');
				$parent->addChild(new XmlText($data));
				break;
			case is_null($data):
				//break;
			default:
				$parent->addChild(new XmlText($data));
				break;
		}

		return $parent;
	}
}