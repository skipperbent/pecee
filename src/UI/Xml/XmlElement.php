<?php
namespace Pecee\UI\Xml;

use Pecee\ArrayUtil;
use Pecee\UI\Html\IHtmlNode;

class XmlElement implements IXmlNode
{

    private $tag;
    private IHtmlNode $parent;
    private array $attrs = [];
    /**
     * @var array|IXmlNode[]
     */
    private array $children = [];
    private string $ns;

    public function __construct($tag = null, array $attrs = [], string $ns = '')
    {
        $this->tag = $tag;
        $this->attrs = $attrs;
        $this->ns = $ns;
    }

    public function getAttrs()
    {
        return $this->attrs;
    }

    public function setAttrs(array $attrs): void
    {
        $this->attrs = $attrs;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function setTag($tag): void
    {
        $this->tag = $tag;
    }

    public function getNs()
    {
        return $this->ns;
    }

    public function setNs($ns): void
    {
        $this->ns = $ns;
    }

    /**
     * @return \Pecee\UI\Phtml\PhtmlNode
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    public function addChild(IXmlNode $node)
    {
        $this->children[] = $node;
        $node->setParent($this);
    }

    /**
     * @return array|IXmlNode[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function setChildren($children): void
    {
        $this->clear();
        $this->addChildren($children);
    }

    public function setAttribute($name, $value = null): void
    {
        $this->attrs[$name] = $value;
    }

    public function getAttribute($name)
    {
        return $this->attrs[$name];
    }

    public function __toString(): string
    {
        return $this->toXml();
    }

    public function getIndex()
    {
        if (!$this->parent) {
            return -1;
        }

        return $this->getParent()->getChildIndex($this);
    }

    public function getChildIndex(IXmlNode $node)
    {
        return array_search($node, $this->children, true);
    }

    public function setChildAt(int $i, IXmlNode $node)
    {
        if ($i < 0 || $i > count($this->children)) {
            throw new \Exception ("Child offset out of bounds: $i. Child count : " . count($this->children));
        }
        unset($this->children[$i]);
        $this->children[$i] = $node;
        ksort($this->children);

        return $this;
    }

    public function removeChildAt(int $i)
    {
        if ($i < 0 || $i > count($this->children)) {
            throw new \Exception ("Child offset out of bounds: $i. Child count : " . count($this->children));
        }
        unset($this->children[$i]);
        $this->children = array_values($this->children);

        return $this;
    }

    public function removeChild(IXmlNode $node)
    {
        return $this->removeChildAt($this->getChildIndex($node));
    }

    public function addChildAt($offset, IXmlNode $node): self
    {
        if ($offset < 0) {
            throw new \Exception ("Child offset must be greater than -1" . count($this->children));
        }

        $result = [];

        if ($offset >= count($this->children)) {
            $this->addChild($node);

            return $this;
        }

        $max = count($this->children);

        for ($i = 0; $i < $max; $i++) {
            $result[] = $this->children[$i];
            if ($i === $offset) {
                $result[] = $node;
                $node->setParent($this);
            }
        }

        $this->children = $result;

        return $this;
    }

    public function addChildren($children): void
    {
        foreach ($children as $node) {
            $this->addChild($node);
        }
    }

    public function addChildrenAt($offset, $children): void
    {
        $i = 0;
        foreach ($children as $node) {
            $this->addChildAt($offset + $i, $node);
            $i++;
        }
    }

    public function detach(): void
    {
        $this->getParent()->removeChild($this);
    }

    public function replace($otherNode): void
    {
        $parent = $this->getParent();
        $i = $parent->getChildIndex($this);
        $this->detach();
        $parent->addChildAt($i, $otherNode);
    }

    public function clear(): void
    {
        foreach ($this->children as $child) {
            $child->setParent(null);
        }
        $this->children = [];
    }

    public function getElementsByTagNameNS($ns, $tagName)
    {
        $result = [];
        $max = count($this->children);
        for ($i = 0; $i < $max; $i++) {
            if (!($this->children[$i] instanceof static)) {
                continue;
            }
            if (strtolower($this->children[$i]->getNs()) === strtolower($ns)
                && strtolower($this->children[$i]->getTag()) === strtolower($tagName)
            ) {
                $result[] = $this->children[$i];
            }
            ArrayUtil::append($result, $this->children[$i]->getElementsByTagNameNS($ns, $tagName));
        }

        return $result;
    }

    public function toXml($makeParent = true)
    {
        $str = "";
        if (!$this->parent && $makeParent) {
            $str = '<?xml version="1.0" encoding="UTF-8" ?>' . chr(10);
        }
        $str .= "<";
        $tagName = '';
        if ($this->getNs() !== '') {
            $tagName .= $this->getNs() . ':';
        }
        $tagName .= $this->tag;
        $str .= $tagName;
        if (count($this->attrs) > 0) {
            $str .= ' ';
            foreach ($this->attrs as $name => $val) {
                $str .= sprintf('%s="%s" ', $name, $val);
            }
            $str = trim($str);
        }
        if (count($this->children) > 0) {
            $str .= '>';
            foreach ($this->children as $child) {
                $str .= $child->__toString();
            }
            $str .= "</$tagName>";
        } else {
            $str .= '/>';
        }

        return $str;
    }

}