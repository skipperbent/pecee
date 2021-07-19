<?php

namespace Pecee\Dataset;

use ArrayIterator;
use IteratorAggregate;

abstract class Dataset implements IteratorAggregate
{

    protected array $data = [];

    abstract protected function create(): void;

    public function toArray(): array
    {
        $this->create();

        $output = [];

        foreach ($this->data as $data) {
            $output[$data['name']] = $data['value'];
        }

        return $output;
    }

    public function getByValue($index)
    {
        foreach ($this->data as $data) {
            if ($data['value'] === $index) {
                return $data['name'];
            }
        }

        return null;
    }

    protected function formatItem(string $name, ?string $value = null): array
    {
        return [
            'name'  => $name,
            'value' => $value,
        ];
    }

    protected function first(string $name, ?string $value = null): self
    {
        array_unshift($this->data, $this->formatItem($value, $name));

        return $this;
    }

    protected function add(string $name, ?string $value = null): self
    {
        $this->data[] = $this->formatItem($name, $value);

        return $this;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
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
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray());
    }

}