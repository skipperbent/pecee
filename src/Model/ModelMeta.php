<?php

namespace Pecee\Model;

use Pecee\Collection\CollectionItem;
use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\ModelMeta\IModelMetaField;

abstract class ModelMeta extends Model
{
    public CollectionItem $data;
    protected ?string $updateIdentifier = null;
    protected bool $mergeData = true;

    public function __construct()
    {
        parent::__construct();

        $this->data = new CollectionItem();

        $hidden = $this->hidden;

        $this->with(['data' => static function (self $object) use ($hidden) {
            $rows = $object->data->getData();

            return array_filter($rows, static function ($key) use ($hidden) {
                return (in_array($key, $hidden, true) === false);
            }, ARRAY_FILTER_USE_KEY);
        }]);
    }

    /**
     * @return ModelCollection|IModelMetaField[]
     */
    abstract protected function fetchData(): ModelCollection;

    abstract protected function onNewDataItem(): IModelMetaField;

    public function onInstanceCreate(): void
    {
        $this->data = new CollectionItem();

        if ($this->isNew() === true) {
            return;
        }

        $collection = [];
        foreach ($this->fetchData() as $item) {

            $key = $item->{$item->getDataKeyName()};
            $output = $item->{$item->getDataValueName()};

            // Add special array types (name[index])
            if (strpos($key, '[') !== false) {

                preg_match_all('/\[([\w]+)\]/', $key, $indexes);

                $key = substr($key, 0, strpos($key, '['));
                $reverse = array_reverse($indexes[1]);

                foreach ($reverse as $index) {
                    $output = [$index => $output];
                }

                if (isset($collection[$key]) === false || is_array($collection[$key]) === false) {
                    $collection[$key] = [];
                }

                $collection[$key] = array_merge_recursive($collection[$key], $output);

                continue;
            }

            // Add default type
            $collection[$key] = $output;
        }

        $this->data->setData($collection);

        $this->updateIdentifier = $this->generateUpdateIdentifier();
    }

    protected function updateData(): void
    {
        if ($this->data === null || $this->isNew() === true || $this->getUpdateIdentifier() === $this->generateUpdateIdentifier()) {
            return;
        }

        $currentFields = $this->fetchData();

        $cf = [];
        foreach ($currentFields as $field) {
            $cf[strtolower($field->{$field->getDataKeyName()})] = $field;
        }

        foreach ($this->data->getData() as $key => $value) {

            if ($value === null) {
                continue;
            }

            if (isset($cf[strtolower($key)]) === true) {
                if ($cf[$key]->value === $value) {
                    unset($cf[$key]);
                    continue;
                }

                /* @var $existingField IModelMetaField */
                $existingField = $cf[$key];
                $existingField->parseFieldData($key, $value);

                unset($cf[$key]);
            } else {
                $field = $this->onNewDataItem();
                $field->parseFieldData($key, $value);
            }
        }

        foreach ($cf as $field) {
            $field->delete();
        }
    }

    /**
     * @param array|null $data
     * @return static
     * @throws Exceptions\ModelException
     * @throws \Pecee\Pixie\Exception
     */
    public function save(array $data = []): self
    {
        $this->mergeData($data);

        $result = parent::save();
        $this->updateData();

        return $result;
    }

    public function setData(array $data): void
    {
        $keys = array_map('strtolower', array_keys($this->getRows()));
        foreach ($data as $key => $d) {
            if (in_array(strtolower($key), $keys, false) === false) {
                $this->data->$key = $d;
            }
        }
    }

    public function toArray(array $filter = []): array
    {
        $rows = parent::toArray($filter);

        if ($this->mergeData === true) {
            $data = $rows['data'] ?? null;
            if ($data !== null) {
                unset($rows['data']);
                $rows += $data;
            }
        }

        return $rows;
    }

    protected function generateUpdateIdentifier(): string
    {
        return md5(serialize($this->data));
    }

    /**
     * Get unique update identifier based on data
     * @return string|null
     */
    public function getUpdateIdentifier(): ?string
    {
        return $this->updateIdentifier;
    }

    public function __get($name)
    {
        if (parent::__isset($name) === true) {
            return parent::__get($name);
        }

        return $this->data->get($name);
    }

    public function __set($name, $value)
    {
        if (parent::__isset($name) === true) {
            parent::__set($name, $value);
        } else {
            $this->data->set($name, $value);
        }
    }

    public function __isset($name)
    {
        if (parent::__isset($name) === false) {
            return ($this->data->exist($name) || array_key_exists(strtolower($name), $this->results['rows']));
        }

        return true;
    }

}