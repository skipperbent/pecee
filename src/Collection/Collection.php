<?php

namespace Pecee\Collection;

class Collection implements \IteratorAggregate, \Countable, \JsonSerializable
{
    protected $rows = [];

    public function __construct(array $rows = null)
    {
        if ($rows !== null) {
            $this->setRows($rows);
        }
    }

    /**
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param array $rows
     */
    public function setRows(array $rows)
    {
        $this->rows = $rows;
    }

    public function hasRows()
    {
        return (\count($this->rows) > 0);
    }

    public function add($item)
    {
        $this->rows[] = $item;
    }

    public function get($index, $defaultValue = null)
    {
        return $this->exist($index) ? $this->rows[$index] : $defaultValue;
    }

    public function exist($index)
    {
        return isset($this->rows[$index]);
    }

    public function remove($index)
    {
        if ($this->exist($index)) {
            unset($this->rows[$index]);
        }
    }

    public function clear()
    {
        $this->rows = [];
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->rows);
    }

    public function count()
    {
        return \count($this->getRows());
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->getRows();
    }
}