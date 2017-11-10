<?php
namespace Pecee\Collection;

class CollectionItem implements \IteratorAggregate
{
    protected $data = [];

    public function __construct(array $rows = null)
    {
        if ($rows !== null) {
            $this->setData($rows);
        }
    }

    public function setData(array $arr)
    {
        $this->data = [];
        foreach ($arr as $key => $value) {
            $this->$key = $value;
        }
    }

    public function get($name)
    {
        return $this->exist($name) ? $this->data[strtolower($name)] : null;
    }

    public function set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($key)
    {
        return $this->exist($key) ? $this->data[strtolower($key)] : null;
    }

    public function __set($key, $value)
    {
        $this->data[strtolower($key)] = $value;
    }

    public function __isset($key)
    {
        return array_key_exists(strtolower($key), $this->data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function add($key, $value)
    {
        $this->$key = $value;
    }

    public function remove($key)
    {
        if ($this->exist($key)) {
            unset($this->data[$key]);
        }
    }

    public function exist($key)
    {
        return isset($this->data[strtolower($key)]);
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