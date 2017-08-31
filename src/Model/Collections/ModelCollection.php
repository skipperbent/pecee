<?php
namespace Pecee\Model\Collections;

use Pecee\Collection\Collection;
use Pecee\Model\ModelData;
use Pecee\Str;

class ModelCollection extends Collection
{

    protected $type;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get first or default value
     * @param string $default
     * @return static|string
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
    public function skip($number)
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
    public function limit($limit)
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
    public function filter($key, $value, $delimiter = '=')
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

                    if($rowKey === $key) {
                        $rowValue = $value;
                    } elseif($row instanceof ModelData && isset($row->data->{$_key}) === true) {
                        $rowValue = $row->data->{$_key};
                    }

                    if($rowValue === null) {
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
                        if ($rowValue === $value) {
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
    public function order($key, $direction = 'desc')
    {
        if ($this->hasRows() === true) {
            $rows = [];
            foreach ($this->getRows() as $row) {
                $k = isset($row->fields[$key]) ? $row->{$key} : $row->data->$key;
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
     * @param array|string|null $filterKeys
     * @return array
     */
    public function toArray($filterKeys = null)
    {
        $output = [];
        /* @var $row \Pecee\Model\Model */

        $filterKeys = (is_string($filterKeys) === true) ? func_get_args() : (array)$filterKeys;

        for ($i = 0, $max = count($this->rows); $i < $max; $i++) {

            $row = $this->rows[$i];

            if($filterKeys === null) {
                $output[] = $row->toArray();
                continue;
            }

            foreach($filterKeys as $key) {
                $output[$key] = $row->{$key};
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
    public function toDataSet($valueRow = 'id', $displayRow = 'id')
    {
        $output = [];
        /* @var $row \Pecee\Model\Model */
        for ($i = 0, $max = count($this->rows); $i < $max; $i++) {
            $row = $this->rows[$i];
            $output[$row->{$valueRow}] = $row->{$displayRow};
        }

        return $output;
    }

}