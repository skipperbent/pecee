<?php
namespace Pecee\Dataset;

abstract class Dataset implements \IteratorAggregate
{

    protected $data = [];

    public function toArray()
    {
        $output = [];

        if (count($this->data)) {
            foreach ($this->data as $data) {
                $output[$data['name']] = $data['value'];
            }
        }

        return $output;
    }

    public function get($index)
    {
        foreach ($this->data as $data) {
            if ($data['value'] === $index) {
                return $data['name'];
            }
        }

        return null;
    }

    protected function add($value = null, $name)
    {
        $arr = [];

        if ($value !== null) {
            $arr['value'] = htmlspecialchars($value);
        }

        $arr['name'] = $name;
        $this->data[] = $arr;

        return $this;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
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
        return new \ArrayIterator($this->toArray());
    }

}