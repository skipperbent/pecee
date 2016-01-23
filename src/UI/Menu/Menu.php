<?php
namespace Pecee\UI\Menu;
use Pecee\UI\Html\Html;

class Menu {
	protected $items;
	protected $class;
	protected $attributes;
	protected $content;

	public function __construct() {
		$this->attributes=array();
		$this->content=array();
		return $this;
	}

	public function getItems() {
		return $this->items;
	}

	/**
	 * Get active tab by index.
	 * @param int $index
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function getItem($index) {
		if($this->hasItem($index)) {
			return $this->items[$index];
		}
		return null;
	}

	/**
	 * Returns first item.
	 * @return \Pecee\UI\Menu\MenuItems|null
	 */
	public function getFirst() {
		if(count($this->items) > 0) {
			foreach($this->items as $item) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Returns last item.
	 * @return \Pecee\UI\Menu\MenuItems|null
	 */
	public function getLast() {
		if(count($this->items) > 0) {
			return $this->items[count($this->items)-1];
		}
		return null;
	}

	/**
	 * Check if the item-index exists.
	 * @param int $index
	 * @return bool
	 */
	public function hasItem($index) {
		return isset($this->items[$index]);
	}

	public function hasItems() {
		return (count($this->items) > 0);
	}

	/**
	 * Add form content
	 * @param \Pecee\UI\Html\Html $element
	 */
	public function addContent(Html $element) {
		$this->content[] = $element;
	}

	/**
	 * Add form content
	 * @param \Pecee\UI\Menu\Menu $element
	 */
	public function addMenu(Menu $element) {
		$this->content[] = $element;
	}

	/**
	 * Get form content, if any
	 * @return \Pecee\UI\Html\Html|null
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Add new item
	 *
	 * @param string $title
	 * @param string $value
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function addItem($title, $value, $description=null) {
		$item = new MenuItems();
		$item->addItem($title, $value, $description);
		$this->items[] = $item;
		return $item;
	}

	/**
	 * Add new item
	 *
	 * @param \Pecee\UI\Menu\MenuItems $item
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function addItemObject(MenuItems $item) {
		$this->items[] = $item;
		return $item;
	}


	/**
	 * Add new item to given index
	 * @param int $index
	 * @param string $title
	 * @param string $value
	 * @param string|null $description
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function addItemToIndex($index, $title, $value, $description=null) {
		$item = new MenuItems();
		$item->addItem( $title, $value, $description );
		$this->items[$index] = $item;
		return $item;
	}

	/**
	 * Set item-class
	 * @param string $name
	 * @return \Pecee\UI\Menu\Menu
	 */
	public function setClass( $name ) {
		$this->class = $name;
		return $this;
	}
	public function addAttribute($name, $value) {
		$this->attributes[$name] = $value;
	}

	public function addClass($class) {
		$this->addAttribute('class', $class);
	}

	public function removeClass() {
		unset($this->attributes['class']);
		return $this;
	}

	protected function getAttributes($attributes) {
		if(is_array($attributes) && count($attributes) > 0) {
			$out = array();
			/* Run through each attribute */
			foreach($attributes as $attr=>$v) {
				$out[] = ' ' . $attr . '="'.$v.'"';
			}
			return join($out, null);
		}
		return '';
	}

	/**
	 * Write html
	 * @return string
	 */
	public function __toString() {
		$o = array();
		if(count($this->items) > 0) {
			$o[] = '<ul'.(($this->class) ? ' class="'.$this->class.'"' : '');
			if(count($this->attributes) > 0) {
				$o[]=$this->getAttributes($this->attributes);
			}
			$o[] = '>';
			foreach($this->items as $item) {
				foreach($item->getItems() as $key=>$i) {
					/* Write html */
					$o[] = sprintf('<li%1$s><a href="%2$s" title="%4$s"%5$s>%3$s</a>',
						$this->getAttributes($i['attributes']),
						$i['value'],
						$i['title'],
						htmlspecialchars($i['description']),
						$this->getAttributes($i['linkAttributes']));
					if(isset($i['content']) && is_array($i['content'])) {
						foreach($i['content'] as $c) {
							$o[]=$c->__toString();
						}
					}
					if(isset($i['menu'])) {
						$o[]=$i['menu']->__toString();
					}
					if(isset($this->content[$key]) > 0) {
						$o[]=$this->content[$key]->__toString();
					}
					$o[]='</li>';
				}
			}

			$o[] = '</ul>';
			return join($o, '');
		}
		return '';
	}
}