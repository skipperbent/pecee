<?php
namespace Pecee\UI\Phtml;

use Pecee\Guid;
use Pecee\Registry;
use Pecee\UI\Html\HtmlElement;

class PhtmlNode extends HtmlElement {
	private static $closureCount = 0;
	private static $append = '';
	private static $prepend = '';
	private $container = false;

	public static function getNextClosure() {
		self::$closureCount++;
		$basis = self::$closureCount . Guid::create();
		return "closure".md5($basis);
	}


	public function isContainer() {
		return $this->container;
	}

	public function setContainer($container) {
		$this->container = $container;
	}

	public function __toString() {
		if ($this->getTag() == 'phtml') {
			return $this->getInnerString();
		}
		return parent::__toString();
	}

	public function getInnerString() {
		$str = '';
        /* @var $child \Pecee\Xml\IXmlNode */
		foreach($this->getChildren() as $child) {
			$str .= $child->__toString();
		}
		return $str;
	}
	public function getInnerPHP() {
		$str = '';
        /* @var $child \Pecee\UI\Phtml\PhtmlNode */
		foreach($this->getChildren() as $child) {
			$str .= $child->toPHP();
		}
		return $str;
	}
	public function toPHP($filename = null) {
		if ($this->getTag() === 'phtml') {
			$result =  $this->getInnerPHP();
			if ($filename !== null) {
                file_put_contents($filename, $result);
            }
			return $result;
		}

		$str = '<';
		$method = false;
		$body = '';

		if ($this->getNs()) {
			$method = true;
		} else {
			$str .= $this->getTag();
		}

		if (count($this->getAttrs()) > 0) {
			if ($method)
				$str .= 'array(';
			else
				$str .= ' ';
			foreach($this->getAttrs() as $name=>$val) {
				if ($method)
					$str .= sprintf('"%s"=>%s,',$name,$this->processAttrValue($val));
				else
					$str .= sprintf('%s="%s" ',$name,$val);
			}
			if ($method)
				$str = trim($str,',').'),';
			else
				$str = trim($str);
		} else if ($method) {
			$str .= 'array(),';
		}

		if ($this->isContainer()) {
			if (!$method) {
				$str .= '>';
			} else {
				$body = '';
			}
			if ($method) {
				$body .= $this->getInnerPHP();
			} else {
				$str .= $this->getInnerPHP();
			}
			if ($method) {
				$taglibs = Registry::getInstance()->get(Phtml::SETTINGS_TAGLIB, array());
				if(isset($taglibs[$this->getNs()])) {
					$tag = $this->getTag();
					$str = $taglibs[$this->getNs()]->callTag($tag,$this->getAttrs(),$body);
				}
			} else {
				$str .= sprintf("</%s>",$this->getTag());
			}
		} else {
			if ($method) {
				$taglibs = Registry::getInstance()->get(Phtml::SETTINGS_TAGLIB, array());
				if(isset($taglibs[$this->getNs()])) {
					$tag = $this->getTag();
					$str = $taglibs[$this->getNs()]->callTag($tag,$this->getAttrs(),null, null);
				}
			} else {
				$str .= '/>';
			}
		}
		if ($this->getParent() == null || $this->getParent()->getTag() == 'phtml') {
			$str = self::$prepend.$str.self::$append;
			self::$prepend = '';
			self::$append = '';
		}

		$str = $this->processEvals($str);
		if ($filename !== null) {
			file_put_contents($filename,$str);
		}
		return $str;
	}
	private function processEvals($phtml) {
		return preg_replace('/%\{([^\}]*)\}/i','<?=$1?>',$phtml);
	}
	private function processAttrValue($val) {
		if (preg_match('/<\?\=(.*)\?>/i',$val)) {
			//Remove php start/end tags that might have gotten here from %{} evaluations
			$val = preg_replace('/<\?\=(.*)\?>/i','$1',$val);
		} else {
			//Replace %{} with ".." - and trim if "".$expr."" exists
			$val = preg_replace('/%\{([^\}]*)\}/i','".$1."','"'.$val.'"');
			$val = preg_replace('/(""\.|\."")/i','',$val);
		}
		return str_replace('&quot;','\\"',$val);
	}
}