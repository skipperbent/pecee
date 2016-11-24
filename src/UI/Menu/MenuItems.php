<?php
namespace Pecee\UI\Menu;

use Pecee\UI\Html\Html;

class MenuItems
{
	protected $data;
	protected $currentItem;

	/**
	 * Add menu
	 * @param \Pecee\UI\Menu\Menu $menu
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function addMenu(Menu $menu)
	{
		$this->currentItem['menu'] = $menu;

		return $this;
	}

	/**
	 * Add new item
	 *
	 * @param string $title
	 * @param string $value
	 * @param string|null $description
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function addItem($title, $value, $description = null)
	{
		$this->moveItem();
		$this->currentItem['title'] = $title;
		$this->currentItem['value'] = $value;
		$this->currentItem['description'] = $description;
		$this->currentItem['attributes'] = null;
		$this->currentItem['linkAttributes'] = null;

		return $this;
	}

	public function getTitle()
	{
		return isset($this->currentItem['title']) ? $this->currentItem['title'] : null;
	}

	public function getValue()
	{
		return isset($this->currentItem['value']) ? $this->currentItem['value'] : null;
	}

	public function setTitle($title)
	{
		$this->currentItem['title'] = $title;

		return $this;
	}

	public function setValue($value)
	{
		$this->currentItem['value'] = $value;

		return $this;
	}

	public function getDescription()
	{
		return isset($this->currentItem['description']) ? $this->currentItem['description'] : null;
	}

	/**
	 * @return \Pecee\UI\Menu\Menu
	 */
	public function getMenu()
	{
		return isset($this->currentItem['menu']) ? $this->currentItem['menu'] : null;
	}

	/**
	 * Moves current element to data array.
	 */
	private function moveItem()
	{
		if ($this->currentItem) {
			$this->data[] = $this->currentItem;
			$this->currentItem = [];
		}
	}

	/**
	 * Adds attribute to item.
	 *
	 * @param string $name
	 * @param string $value
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function addAttribute($name, $value)
	{
		if (isset($this->currentItem['attributes'][$name])) {
			$tmp = $this->currentItem['attributes'][$name];
			unset($this->currentItem['attributes'][$name]);
			$this->currentItem['attributes'][$name] = $tmp . ' ' . $value;

			return $this;
		}
		$this->currentItem['attributes'][$name] = $value;

		return $this;
	}

	public function removeAttribute($name)
	{
		if (isset($this->currentItem['attributes'][$name])) {
			unset($this->currentItem['attributes'][$name]);
		}
	}

	/**
	 * Adds attribute to item.
	 *
	 * @param string $name
	 * @param string $value
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function addLinkAttribute($name, $value)
	{
		if (isset($this->currentItem['linkAttributes'][$name])) {
			$tmp = $this->currentItem['linkAttributes'][$name];
			unset($this->currentItem['linkAttributes'][$name]);
			$this->currentItem['linkAttributes'][$name] = $tmp . ' ' . $value;

			return $this;
		}
		$this->currentItem['linkAttributes'][$name] = $value;

		return $this;
	}

	/**
	 * Add form content
	 * @param \Pecee\UI\Html\Html $element
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function addContent(Html $element)
	{
		$this->currentItem['content'][] = $element;

		return $this;
	}

	/**
	 * Get form content, if any
	 * @return \Pecee\UI\Html\Html|null
	 */
	public function getContent()
	{
		return $this->currentItem['content'];
	}

	public function removeLinkAttribute($name)
	{
		if (isset($this->currentItem['linkAttributes'][$name])) {
			unset($this->currentItem['linkAttributes'][$name]);
		}
	}

	/**
	 * Add class to item
	 * @param string $name
	 * @return \Pecee\UI\Menu\MenuItems
	 */
	public function addClass($name)
	{
		$this->addAttribute('class', $name);

		return $this;
	}

	public function getItems()
	{
		$this->moveItem();

		return $this->data;
	}
}