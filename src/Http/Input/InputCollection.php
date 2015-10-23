<?php
namespace Pecee\Http\Input;

use Pecee\Collection\CollectionItem;

class InputCollection extends CollectionItem {

    /**
     * Search for input element matching index.
     * Useful for searching for finding items where $index doesn't contain form name.
     *
     * @param string $index
     * @return mixed
     */
    public function findFirst($index) {
        if(count($this->data)) {

            if(isset($this->data[$index])) {
                return $this->data[$index];
            }

            foreach($this->data as $key => $value) {

                if(strpos($key, '_') !== false) {
                    $form = substr($key, 0, strpos($key, '_'));
                    $key = substr($key, strlen($form)+1);

                    if(strtolower($index) === strtolower($key)) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param $index
     * @throws \InvalidArgumentException
     * @return IInputItem
     */
    public function __get($index) {
        $item = $this->findFirst($index);
        // Ensure that item are always availible
        if($item === null) {
            $this->data[$index] = new InputItem($index, null);
            return $this->data[$index];
        }
        return $item;
    }

}