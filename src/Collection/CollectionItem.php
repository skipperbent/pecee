<?php
namespace Pecee\Collection;

class CollectionItem implements \IteratorAggregate
{
    protected array $data = [];

    public function __construct(array $rows = null)
    {
        if ($rows !== null) {
            $this->setData($rows);
        }
    }

    public function setData(array $arr): void
    {
        $this->data = [];
        foreach ($arr as $key => $value) {
            $this->$key = $value;
        }
    }

    public function get(string $name, $defaultValue = null)
    {
        return $this->data[strtolower($name)] ?? $defaultValue;
    }

    public function set(string $name, $value): void
    {
        $this->$name = $value;
    }

    public function __get(string $key)
    {
        return $this->exist($key) ? $this->data[strtolower($key)] : null;
    }

    public function __set(string $key, $value): void
    {
        $this->data[strtolower($key)] = $value;
    }

    public function __isset(string $key): bool
    {
        return $this->exist($key);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function add(string $key, $value): void
    {
        $this->$key = $value;
    }

    public function remove(string $key): void
    {
        if ($this->exist($key)) {
            unset($this->data[$key]);
        }
    }

    public function exist(string $key): bool
    {
        return isset($this->data[strtolower($key)]);
    }

    /**
     * Perform action on each item
     * @param callable $callback
     * @return static
     */
    public function each(callable $callback): self
    {
        foreach($this->getData() as $key => $row) {
            $callback($row, $key);
        }

        return $this;
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
        return new \ArrayIterator($this->data);
    }

}