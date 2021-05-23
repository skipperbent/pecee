<?php
namespace Pecee\UI\Menu;

use Pecee\UI\Html\Html;

class Menu
{
    protected $items = [];
    protected $attributes = [];
    protected $content = [];
    protected $class;
    protected $parent;

    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get active tab by index.
     * @param int $index
     * @return \Pecee\UI\Menu\MenuItem
     */
    public function getItem($index)
    {
        return $this->hasItem($index) ? $this->items[$index] : null;
    }

    /**
     * Returns first item.
     * @return \Pecee\UI\Menu\MenuItem|null
     */
    public function getFirst()
    {
        if (count($this->items)) {
            return $this->items[0];
        }

        return null;
    }

    /**
     * Returns last item.
     * @return \Pecee\UI\Menu\MenuItem|null
     */
    public function getLast()
    {
        return end($this->items);
    }

    /**
     * Check if the item-index exists.
     * @param int $index
     * @return bool
     */
    public function hasItem($index)
    {
        return isset($this->items[$index]);
    }

    public function hasItems()
    {
        return count($this->items);
    }

    /**
     * Add form content
     * @param \Pecee\UI\Html\Html $element
     */
    public function addContent(Html $element)
    {
        $this->content[] = $element;
    }

    /**
     * Add form content
     * @param \Pecee\UI\Menu\Menu $element
     */
    public function addMenu(Menu $element)
    {
        $this->content[] = $element;
    }

    /**
     * Get form content, if any
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Add new item
     *
     * @param string $name
     * @param string $url
     * @return \Pecee\UI\Menu\MenuItem
     */
    public function addItem($name, $url)
    {
        $item = new MenuItem($name, $url);
        $item->setParent($this);
        $this->items[] = $item;

        return $item;
    }

    /**
     * Add new item
     *
     * @param \Pecee\UI\Menu\MenuItem $item
     * @return static
     */
    public function addMenuItem(MenuItem $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Set item-class
     * @param string $name
     * @return static
     */
    public function setClass($name)
    {
        $this->class = $name;

        return $this;
    }

    public function addAttribute($name, $value)
    {
        if (isset($this->attributes[$name])) {
            $this->attributes[$name][] = $value;
        } else {
            $this->attributes[$name] = [$value];
        }

        return $this;
    }

    /**
     * @param string $class
     * @return Menu
     */
    public function addClass($class)
    {
        return $this->addAttribute('class', $class);
    }

    /**
     * @return static $this
     */
    public function removeClass()
    {
        unset($this->attributes['class']);

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set parent menu-item
     *
     * @param MenuItem $parent
     * @return static
     */
    public function setParent(MenuItem $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent menu-item
     * @return MenuItem|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function findItemByUrl($url, $strict = false, $recursive = true)
    {
        /* @var $item MenuItem */
        foreach ($this->items as $item) {

            if ($recursive === true && $item->getMenu() !== null) {
                $subItem = $item->getMenu()->findItemByUrl($url, $strict, $recursive);
                if ($subItem !== null) {
                    return $subItem;
                }
            }

            if ($strict === true) {
                if (rtrim($item->getUrl(), '/') === rtrim($url, '/')) {
                    return $item;
                }
            } else {
                $itemUrl = rtrim($item->getUrl(), '/');
                $itemUrl = ($itemUrl === '') ? '/' : $itemUrl;

                $url = rtrim($url, '/');
                $url = ($url === '') ? '/' : $url;

                if (false !== stripos($itemUrl, $url)) {
                    return $item;
                }
            }

        }

        return null;
    }

    public function findItemByAttribute($name, $value, $strict = false, $recursive = true)
    {
        /* @var $item MenuItem */
        foreach ($this->items as $item) {

            if ($recursive === true && $item->getMenu() !== null) {
                $subItem = $item->getMenu()->findItemByAttribute($name, $value, $strict, $recursive);
                if ($subItem !== null) {
                    return $subItem;
                }
            }

            $attributes = $item->getAttributes();

            if ($attributes !== null && isset($attributes[$name])) {

                if ($strict === true) {
                    if (in_array($value, $attributes[$name], true) === true) {
                        return $item;
                    }
                } else if (stripos($attributes[$name], $value) !== false) {
                    return $item;
                }

            }
        }

        return null;
    }

    /**
     * @param array $attributes
     * @return string
     */
    protected function formatAttributes(array $attributes)
    {
        if (count($attributes)) {
            $output = ' ';
            /* Run through each attribute */
            foreach ($attributes as $name => $value) {
                $output .= $name . '="' . join(' ', $value) . '"';
            }

            return $output;
        }

        return '';
    }

    /**
     * Write html
     * @return string
     */
    public function __toString()
    {
        $o = '';
        if (count($this->items)) {

            $o .= '<ul' . ($this->class ? ' class="' . $this->class . '"' : '');

            if (count($this->attributes)) {
                $o .= $this->formatAttributes($this->attributes);
            }

            $o .= '>';

            /* @var $menuItem MenuItem */
            foreach ($this->items as $key => $menuItem) {
                /* Write html */

                $o .= '<li' . $this->formatAttributes($menuItem->getAttributes()) . '><a href="' . $menuItem->getUrl() . '"' .
                    $this->formatAttributes($menuItem->getLinkAttributes()) . '>' .
                    htmlspecialchars($menuItem->getName()) . '</a>';

                $inner = $menuItem->getInnerContent();

                if ($inner !== null) {
                    $o .= $inner;
                }

                $menu = $menuItem->getMenu();

                if ($menu !== null) {
                    $o .= $menu->__toString();
                }

                if (isset($this->content[$key])) {
                    $o .= $this->content[$key]->__toString();
                }

                $o .= '</li>';
            }

            $o .= '</ul>';

            return $o;
        }

        return '';
    }
}