<?php

namespace Pecee\Model\Collections;

use Pecee\Collection\Collection;
use Pecee\Model\ModelData;
use Pecee\Str;

class ModelCollection extends Collection
{

    protected string $type = '';

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get first or default value
     * @param string $default
     * @return static|null|mixed
     */
    public function getFirstOrDefault($default = null)
    {
        return $this->get(0, $default);
    }

    /**
     * Skip number of rows
     * @param int $number
     * @return static
     */
    public function skip(int $number): self
    {
        if ($number > 0 && $this->hasRows() === true) {
            $rows = $this->getRows();
            return new static(array_splice($rows, $number));
        }

        return $this;
    }

    /**
     * Limit the output
     * @param int $limit
     * @return static
     */
    public function limit(int $limit)
    {
        $out = [];
        if ($this->hasRows()) {
            foreach ($this->getRows() as $i => $row) {
                if ($i < $limit) {
                    $out[] = $row;
                }
            }
        }

        return new static($out);
    }

    /**
     * Remove item from array based on key/value.
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function removeByFilter(string $key, string $value): self
    {
        $this->each(function ($item, $key) {
            if ($item->{$key} === $value) {
                $this->remove($key);
            }
        });

        return $this;
    }

    /**
     * Filter elements
     * @param string|array $key
     * @param string $value
     * @param string $delimiter
     * @return static
     */
    public function filter(string $key, string $value, string $delimiter = '='): self
    {
        $out = [];
        if ($this->hasRows()) {

            $keys = (array)$key;

            /* @var $row \Pecee\Model\Model */
            foreach ($this->getRows() as $rowKey => $row) {

                if (in_array($row, $out, true) !== false) {
                    continue;
                }

                foreach ($keys as $_key) {

                    $rowValue = null;

                    if ($row->{$_key} !== null) {
                        $rowValue = $row->{$_key};
                    } elseif ($rowKey === $key) {
                        $rowValue = $value;
                    } elseif ($row instanceof ModelData && isset($row->data->{$_key}) === true) {
                        $rowValue = $row->data->{$_key};
                    }

                    if ($rowValue === null) {
                        continue;
                    }

                    if ($delimiter === '>') {
                        if ($rowValue > $value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '<') {
                        if ($rowValue < $value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '>=') {
                        if ($rowValue >= $value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '<=') {
                        if ($rowValue <= $value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '!=') {
                        if ((string)$rowValue !== (string)$value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '*') {
                        if (strtolower($rowValue) === (string)$value || stripos($rowValue, $value) !== false) {
                            $out[] = $row;
                        }
                    } else {
                        if ((string)$rowValue === $value) {
                            $out[] = $row;
                        }
                    }
                }
            }
        }

        return new static($out);
    }

    /**
     * Order by key
     * @param string $key
     * @param string $direction
     * @return static
     */
    public function order(string $key, string $direction = 'desc')
    {
        if ($this->hasRows() === true) {
            $rows = [];
            foreach ($this->getRows() as $row) {
                $k = $row->fields[$key] ?? $row->data->{$key};
                $k = ((string)$k === 'Tjs=') ? Str::base64Decode($k) : $k;
                $rows[$k] = $row;
            }

            if (strtolower($direction) === 'asc') {
                ksort($rows);
            } else {
                krsort($rows);
            }

            return new static(array_values($rows));
        }

        return $this;
    }

    /**
     * Get array
     *
     * @param array|string|null $filterKeys
     * @return array
     */
    public function toArray($filterKeys = null): array
    {
        $output = [];
        foreach ($this->getRows() as $row) {
            if ($filterKeys === null) {
                $output[] = $row->toArray();
                continue;
            }

            $output[] = $row->toArray(is_array($filterKeys) ? $filterKeys : [$filterKeys]);
        }

        return $output;
    }

    /**
     * To dataset
     *
     * @param string $valueRow
     * @param string $displayRow
     * @return array
     */
    public function toDataSet(string $valueRow = 'id', string $displayRow = 'id'): array
    {
        $output = [];
        /* @var $row \Pecee\Model\Model */
        for ($i = 0, $max = count($this->rows); $i < $max; $i++) {
            $row = $this->rows[$i];
            $output[$row->{$displayRow}] = $row->{$valueRow};
        }

        return $output;
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
        return $this->toArray();
    }

}