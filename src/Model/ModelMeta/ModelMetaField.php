<?php
namespace Pecee\Model\ModelMeta;

use Pecee\Model\Model;

abstract class ModelMetaField extends Model implements IModelMetaField
{

    public function onMapData(string $key, $value): void
    {
        $this->{$this->getDataKeyName()} = $key;
        $this->{$this->getDataValueName()} = $value;
    }

    public function parseFieldData(string $key, $data): void
    {
        // Single value item
        if (is_array($data) === false) {
            $this->onMapData($key, $data);
            $this->save();
            return;
        }

        // Parse array data
        foreach ($data as $k => $v) {

            // Parse multi dimensional array
            if (is_array($v) === true) {

                if(count($v) === 0) {
                    continue;
                }

                $this->parseFieldData($key . '[' . $k . ']', $v);
                continue;
            }

            // Ignore empty values
            if(trim($v) === '') {
                continue;
            }
            
            $field = clone $this;
            // Make sure fixed identifiers work
            $field->{$this->getPrimaryKey()} = (new static())->{$this->getPrimaryKey()};
            $field->onMapData($key . '[' . $k . ']', $v);
            $field->save();
        }
    }

}