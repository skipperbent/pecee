<?php

namespace Pecee\Model;

use Pecee\Collection\CollectionItem;

abstract class ModelData extends Model
{
    public CollectionItem $data;
    protected string $dataPrimary = '';
    protected ?string $updateIdentifier = null;
    protected string $dataKeyField = 'key';
    protected string $dataValueField = 'value';
    protected bool $mergeData = true;

    public function __construct()
    {
        parent::__construct();

        if ($this->dataPrimary === '') {
            throw new \ErrorException('Property dataPrimary must be defined');
        }

        $this->data = new CollectionItem();

        $this->with(['data' => function (self $object) {
            $rows = $object->data->getData();

            return array_filter($rows, function ($key) {
                return (in_array($key, $this->hidden, true) === false);
            }, ARRAY_FILTER_USE_KEY);
        }]);
    }

    abstract protected function getDataClass();

    abstract protected function fetchData(): \IteratorAggregate;

    protected function onNewDataItemCreate(Model $field): void
    {
        $field->{$this->getDataPrimary()} = $this->{$this->primaryKey};
        $field->save();
    }

    public function onInstanceCreate(): void
    {
        $this->data = new CollectionItem();

        if ($this->isNew() === true) {
            return;
        }

        $collection = [];
        foreach ($this->fetchData() as $d) {

            $key = $d->{$this->dataKeyField};
            $output = $d->{$this->dataValueField};

            // Add special array types (name[index])
            if (strpos($key, '[') !== false) {

                preg_match_all('/\[([\w]+)\]/', $d->{$this->dataKeyField}, $indexes);

                $key = substr($key, 0, strpos($key, '['));

                $reverse = array_reverse($indexes[1]);
                $max = count($reverse);

                for ($i = 0; $i < $max; $i++) {
                    $index = $reverse[$i];
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

    protected function setFieldData(Model $model, string $key, $data, \Closure $callback): void
    {
        if (is_array($data) === true) {
            foreach ($data as $k => $v) {

                if (is_array($v) === true) {

                    if(count($v) === 0) {
                        continue;
                    }

                    $this->setFieldData($model, $key . '[' . $k . ']', $v, $callback);
                    continue;
                }

                if(trim($v) === '') {
                    continue;
                }

                $field = $this->getDataClass();
                $field = new $field();
                $field->{$this->dataKeyField} = $key . '[' . $k . ']';
                $field->{$this->dataValueField} = $v;
                $this->onNewDataItemCreate($field);
                $callback($field);

            }
        } else {
            $model->{$this->dataKeyField} = $key;
            $model->{$this->dataValueField} = $data;
            $callback($model);
        }
    }

    protected function updateData(): void
    {
        if ($this->isNew() === true) {
            return;
        }

        if ($this->data !== null && $this->getUpdateIdentifier() !== $this->generateUpdateIdentifier()) {

            /* @var $currentFields array|null */
            $currentFields = $this->fetchData();

            if ($currentFields === null) {
                return;
            }

            $cf = [];
            foreach ($currentFields as $field) {
                $cf[strtolower($field->{$this->dataKeyField})] = $field;
            }

            if (count($this->data->getData())) {

                foreach ($this->data->getData() as $key => $value) {

                    if ($value === null) {
                        continue;
                    }

                    if (isset($cf[strtolower($key)]) === true) {
                        if ($cf[$key]->value === $value) {
                            unset($cf[$key]);
                            continue;
                        }

                        $this->setFieldData($cf[$key], $key, $value, function (Model $model) {
                            $model->save();
                        });

                        unset($cf[$key]);

                    } else {
                        $field = $this->getDataClass();
                        $field = new $field();

                        $this->setFieldData($field, $key, $value, function (Model $model) {
                            $this->onNewDataItemCreate($model);
                        });
                    }
                }
            }

            foreach ($cf as $field) {
                $field->delete();
            }
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

    public function getDataPrimary()
    {
        return $this->dataPrimary;
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