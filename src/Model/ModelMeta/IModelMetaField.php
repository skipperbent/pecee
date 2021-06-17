<?php
namespace Pecee\Model\ModelMeta;

interface IModelMetaField
{
    /**
     * Triggered when meta field maps data to fields.
     * @param string $key
     * @param mixed $value
     */
    public function onMapData(string $key, $value): void;

    /**
     * Get name for data key column.
     * @return string
     */
    public function getDataKeyName(): string;

    /**
     * Get name for data value column.
     * @return string
     */
    public function getDataValueName(): string;

    /**
     * @param string $key
     * @param mixed $data
     */
    public function parseFieldData(string $key, $data): void;

    /**
     * @param array $data
     * @return static
     */
    public function save(array $data = []);

}