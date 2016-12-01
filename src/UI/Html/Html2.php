<?php
namespace Pecee\UI\Html;

class Html2
{
    const CLOSE_TYPE_SELF = 'self';
    const CLOSE_TYPE_TAG = 'tag';
    const CLOSE_TYPE_NONE = 'none';

    protected $tag;
    protected $closingType;
    protected $innerHtml = [];
    protected $attributes = [];
    protected $parent;
    protected $children = [];

    public function __construct($tag = null)
    {
        $this->tag = $tag;
        $this->closingType = static::CLOSE_TYPE_SELF;
    }

    public function find($selector) {

        $selector = trim($selector);

        $queries = explode(',', $selector);

        $childNodes = [];

        foreach($queries as $query) {

            $scopeQuery = explode(' ', $query);
            $query = trim($scopeQuery[0]);

            $types = [];
            $classes = [];
            $ids = [];
            $attrs = [];

            preg_match('/^\w+/is', $query, $types);
            preg_match_all('/[\.]([^\[.#:\s\*]+)/is', $query, $classes);
            preg_match_all('/[\#]([^\[.#:\s\*]+)/is', $query, $ids);
            preg_match_all('/\[([\w-]+)([\!\$\|\^\~\*]{0,1}\=)?["\']?([\w-]+)?["\']?\]/i', $query, $attrs);

            /* @var $child static */
            foreach($this->children as $child) {

                if(count($types) > 0 && strtolower($child->getType()) !== strtolower($types[0])) {
                    continue;
                }

                if(count($classes[1]) && $child->hasClass($classes[1]) === false) {
                    continue;
                }

                if($child->attr('id') !== null && count($ids[1]) && count(array_diff($child->attr('id'), $ids[1])) !== 0) {
                    continue;
                }

                if(count($attrs[1])) {

                    $matches = true;

                    foreach($attrs[1] as $i => $attr) {

                        $comparator = $attrs[2][$i];

                        if($child->hasAttribute($attr) === false) {
                            $matches = false;
                            break;
                        }

                        $childAttr = $child->parseAttribute($attr);

                        if($comparator === '=' && $childAttr != $attrs[3][$i]) {
                            $matches = false;
                            break;
                        }

                        if($comparator === '!=' && $childAttr == $attrs[3][$i]) {
                            $matches = false;
                            break;
                        }

                        if($comparator === '|=' && stripos($childAttr, $attrs[3][$i] . '-') === -1) {
                            $matches = false;
                            break;
                        }

                        if($comparator === '^=' && stripos($childAttr, $attrs[3][$i]) !== 0) {
                            $matches = false;
                            break;
                        }

                        if($comparator === '*=' && stripos($childAttr, $attrs[3][$i]) === -1) {
                            $matches = false;
                            break;
                        }

                        if($comparator === '~=' && (stripos($childAttr, ' ' . $attrs[3][$i]) === -1 && stripos($childAttr, $attrs[3][$i] . ' ') === -1)) {
                            $matches = false;
                            break;
                        }

                    }

                    if($matches === false) {
                        continue;
                    }

                }

                $childNodes[] = $child;
            }

            if(count($scopeQuery) > 1) {

                /* @var $match self */
                foreach ($childNodes as $key => $match) {
                    $childNodes[$key] = $match->find($scopeQuery[$key + 1]);
                }

            }
        }

        return $childNodes;

    }

    public function wrap($type) {

        $element = new static($type);



    }

    public function append($element) {
        $this->children[] = $element;
        return $this;
    }

    public function prepend($element) {
        $this->parent()->append($element);
        return $this;
    }

    /**
     * Get parent element
     *
     * @return static|null
     */
    public function parent() {
        return $this->parent;
    }

    public function child() {
        return $this->children;
    }

    public function addClass($class) {
        return $this->attr('class', $class);
    }

    public function hasClass($name) {
        $classes = $this->attr('class');

        if($classes !== null) {

            if(is_array($name) === true && count(array_diff($classes, $name)) === 0) {
                return true;
            }

            return in_array($name, $classes);

        }

        return false;
    }


    public function removeClass($name) {
        $this->attributes['class'] = trim(preg_replace('/\s+/', ' ', str_ireplace($name, '', $this->attributes['class'])));
        return $this;
    }

    public function hasAttribute($name) {
        return isset($this->attributes[$name]);
    }

    public function getAttribute($name) {
        return $this->hasAttribute($name) ? $this->attributes[$name] : null;
    }

    public function attr($name, $value = false) {

        if($value === false) {
            return $this->getAttribute($name);
        }

        if($value === null) {
            $this->removeAttr($name);
        }

        if(isset($this->attributes[$name]) === false) {
            $this->attributes[$name] = [$value];
            return $this;
        }

        if(in_array($name, $this->attributes[$name]) === false) {
            $this->attributes[$name][] = $value;
        }

        return $this;
    }

    protected function parseAttribute($name = null) {
        return ($this->hasAttribute($name)) ? join(' ', $this->attributes[$name]) : '';
    }

    public function removeAttr($name) {
        if(isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }
    }

    public function getType() {
        return $this->tag;
    }

    public function __toString()
    {
        return $this->render();
    }

    protected function render()
    {
        $output = '<' . $this->tag;

        foreach ($this->attributes as $key => $val) {
            $output .= ' ' . $key;
            if ($val[0] !== null || strtolower($key) === 'value') {
                $val = htmlentities(join(' ', $val), ENT_QUOTES, app()->getCharset());
                $output .= '="' . $val . '"';
            }
        }

        $output .= '>' . join('', $this->innerHtml);

        $output .= (($this->closingType === self::CLOSE_TYPE_TAG) ? sprintf('</%s>', $this->tag) : '');

        return $output;
    }

}