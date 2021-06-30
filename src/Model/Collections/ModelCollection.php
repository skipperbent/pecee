<?php

namespace Pecee\Model\Collections;

use Pecee\Collection\Collection;
use Pecee\Model\Model;
use Pecee\Model\ModelMeta;
use Pecee\Str;

class ModelCollection extends Collection
{

    /**
     * @var array|Model[]
     */
    protected array $rows = [];

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
     * @param string|null $default
     * @return static|null|mixed
     */
    public function getFirstOrDefault(?string $default = null)
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
            $this->setRows(array_splice($rows, $number));
        }

        return $this;
    }

    /**
     * Limit the output
     * @param int $limit
     * @return static
     */
    public function limit(int $limit): self
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
     * Filter elements
     * @param string|array $key
     * @param string $value
     * @param string $delimiter
     * @return static
     */
    public function filter(string $key, string $value, string $delimiter = '='): self
    {
        $out = [];
        if ($this->hasRows() === true) {

            $keys = (array)$key;

            foreach ($this->getRows() as $rowKey => $row) {

                if (in_array($row, $out, true) !== false) {
                    continue;
                }

                foreach ($keys as $_key) {

                    $rowValue = null;

                    if ($rowKey === $_key) {
                        $rowValue = $value;
                    } elseif ($row instanceof ModelMeta && isset($row->{$_key}) === true) {
                        $rowValue = $row->{$_key};
                    }

                    if ($rowValue === null) {
                        continue;
                    }

                    switch ($delimiter) {
                        default:
                        {
                            if ($rowValue === $value) {
                                $out[] = $row;
                            }
                            break;
                        }
                        case '>':
                        {
                            if ($rowValue > $value) {
                                $out[] = $row;
                            }
                            break;
                        }
                        case '<':
                        {
                            if ($rowValue < $value) {
                                $out[] = $row;
                            }
                            break;
                        }
                        case '>=':
                        {
                            if ($rowValue >= $value) {
                                $out[] = $row;
                            }
                            break;
                        }
                        case '<=':
                        {
                            if ($rowValue <= $value) {
                                $out[] = $row;
                            }
                            break;
                        }
                        case '!=':
                        case '!==':
                        {
                            if ((string)$rowValue !== $value) {
                                $out[] = $row;
                            }
                            break;
                        }
                        case '*':
                        {
                            if (strtolower($rowValue) === $value || stripos($rowValue, $value) !== false) {
                                $out[] = $row;
                            }
                            break;
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
    public function order(string $key, string $direction = 'desc'): self
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
        $filterKeys = ($filterKeys !== null && is_string($filterKeys) === true) ? func_get_args() : $filterKeys;

        $output = [];

        if ($filterKeys !== null) {
            foreach ($filterKeys as $key) {
                $output[$key] = [];
            }
        }

        for ($i = 0, $max = count($this->rows); $i < $max; $i++) {

            $row = $this->rows[$i]->toArray();

            if ($filterKeys === null) {
                $output[] = $row;
                continue;
            }

            foreach ($filterKeys as $key) {
                $output[$key][] = $row[$key];
            }

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
        foreach ($this->rows as $row) {
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
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}