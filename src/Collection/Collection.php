<?php

namespace Pecee\Collection;

use Exception;

class Collection implements \IteratorAggregate, \Countable, \JsonSerializable, \Serializable
{
    protected array $rows = [];

    public function __construct(array $rows = null)
    {
        if ($rows !== null) {
            $this->setRows($rows);
        }
    }

    /**
     * @return array
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @param array $rows
     */
    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }

    public function hasRows(): bool
    {
        return (\count($this->rows) > 0);
    }

    public function isEmpty(): bool
    {
        return !$this->hasRows();
    }

    public function add($item): void
    {
        $this->rows[] = $item;
    }

    public function get(string $index, $defaultValue = null)
    {
        return $this->exist($index) ? $this->rows[$index] : $defaultValue;
    }

    public function exist(string $index): bool
    {
        return isset($this->rows[$index]);
    }

    public function remove(string $index): void
    {
        if ($this->exist($index)) {
            unset($this->rows[$index]);
        }
    }

    public function clear(): void
    {
        $this->rows = [];
    }

    /**
     * Perform action on each item
     * @param callable $callback
     * @return static
     */
    public function each(callable $callback): self
    {
        foreach ($this->getRows() as $key => $row) {
            $callback($row, $key);
        }

        return $this;
    }

    /**
     * Filter array with callback
     * @param callable $callback
     * @return array
     */
    public function filterArray(callable $callback): array
    {
        $collection = [];
        foreach ($this->getRows() as $row) {
            $collection[] = $callback($row);
        }

        return $collection;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->rows);
    }

    public function count(): int
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
    public function jsonSerialize(): mixed
    {
        return $this->getRows();
    }

    public function serialize()
    {
        return $this->getRows();
    }

    public function unserialize($data)
    {
        $this->setRows((array)$data);
    }

    public function __serialize(): array
    {
        return $this->getRows();
    }

    public function __unserialize(array $data): void
    {
        $this->setRows($data);
    }
}