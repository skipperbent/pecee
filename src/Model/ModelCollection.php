<?php
namespace Pecee\Model;

use Pecee\Collection\Collection;

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
    public function toDataset($valueRow = 'id', $displayRow = 'id')
    {
        $output = [];
        /* @var $row Model */
        for ($i = 0, $max = count($this->rows); $i < $max; $i++) {
            $row = $this->rows[$i];
            $output[$row->{$valueRow}] = $row->{$displayRow};
        }

        return $output;
    }

}