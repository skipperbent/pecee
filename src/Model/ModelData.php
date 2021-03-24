<?php

namespace Pecee\Model;

use Pecee\Collection\CollectionItem;

abstract class ModelData extends Model
{
    public $data;
    protected $dataPrimary;
    protected $updateIdentifier;
    protected $dataKeyField = 'key';
    protected $dataValueField = 'value';
    protected $mergeData = true;

    public function __construct()
    {
        parent::__construct();

        if ($this->dataPrimary === null) {
            throw new \ErrorException('Property dataPrimary must be defined');
        }

        $this->data = new CollectionItem();
    }

    abstract protected function getDataClass();

    abstract protected function fetchData();

    protected function onNewDataItemCreate(Model $field)
    {
        $field->{$this->getDataPrimary()} = $this->{$this->primary};
        $field->save();
    }

    public function onInstanceCreate()
    {
        $this->data = new CollectionItem();
        
        if($this->isNew() === true) {
            return;
        }

        /* @var $data array */
        $data = $this->fetchData();
        foreach ($data as $d) {
            $this->data->{$d->{$this->dataKeyField}} = $d->{$this->dataValueField};
        }

        $this->updateIdentifier = $this->generateUpdateIdentifier();
    }

    protected function updateData()
    {
        if($this->isNew() === true) {
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

                        $cf[$key]->{$this->dataKeyField} = $key;
                        $cf[$key]->{$this->dataValueField} = $value;
                        $cf[$key]->save();
                        unset($cf[$key]);

                    } else {
                        $field = $this->getDataClass();
                        $field = new $field();
                        $field->{$this->dataKeyField} = $key;
                        $field->{$this->dataValueField} = $value;

                        $this->onNewDataItemCreate($field);
                    }
                }
            }

            foreach ($cf as $field) {
                $field->delete();
            }
        }
    }

    public function save(array $data = null)
    {
        if($data !== null && count($data) > 0) {
            foreach($data as $key => $value) {
                $this->{$key} = $value;
            }
        }

        parent::save();
        $this->updateData();
    }

    public function setData(array $data)
    {
        $keys = array_map('strtolower', array_keys($this->getRows()));
        foreach ($data as $key => $d) {
            if (in_array(strtolower($key), $keys, false) === false) {
                $this->data->$key = $d;
            }
        }
    }

    public function toArray(array $filter = [])
    {
        $rows = parent::toArray($filter);

        if ($this->mergeData === true) {
            $rows += $this->data->getData();
        } else {
            $rows['data'] = $this->data->getData();
        }

        return array_filter($rows, function ($key) {
            return (in_array($key, $this->hidden, true) === false);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function generateUpdateIdentifier()
    {
        return md5(serialize($this->data));
    }

    /**
     * Get unique update identifier based on data
     * @return string
     */
    public function getUpdateIdentifier()
    {
        return $this->updateIdentifier;
    }

    public function getDataPrimary()
    {
        return $this->dataPrimary;
    }

    public function __get($name)
    {
        $exists = parent::__isset($name);

        if ($exists === true) {
            return parent::__get($name);
        }

        return $this->data->{$name};
    }

    public function __set($name, $value)
    {
        $exists = parent::__isset($name);

        if ($exists === true) {
            parent::__set($name, $value);
        } else {
            $this->data->{$name} = $value;
        }
    }

    public function __isset($name)
    {
        $exists = parent::__isset($name);

        if ($exists === false) {
            return array_key_exists(strtolower($name), $this->results['rows']);
        }

        return $exists;
    }

}