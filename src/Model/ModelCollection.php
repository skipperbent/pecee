<?php
namespace Pecee\Model;

use Pecee\Collection\Collection;

class ModelCollection extends Collection {

    protected $type;

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    public function toArray() {
        $output = array();
        /* @var $row \Pecee\Model\Model */
        foreach($this->rows as $row) {
            $output[] = $row->toArray();
        }
        return $output;
    }

}