<?php

namespace Pecee\Model\File;

use Pecee\Model\ModelMeta\ModelMetaField;

class FileData extends ModelMetaField
{
    protected bool $timestamps = false;
    protected string $table = 'file_data';

    protected array $columns = [
        'id',
        'file_id',
        'key',
        'value',
    ];

    public function __construct($fileId = null, $key = null, $value = null)
    {
        parent::__construct();

        $this->file_id = $fileId;
        $this->key = $key;
        $this->value = $value;
    }

    public function getDataKeyName(): string
    {
        return 'key';
    }

    public function getDataValueName(): string
    {
        return 'value';
    }

    public function exists(): bool
    {
        if ($this->{$this->primaryKey} === null) {
            return false;
        }

        return ($this->where('key', '=', $this->key)->where('file_id', '=', $this->file_id)->count('id') > 0);
    }

    public static function destroyByFileId($fileId)
    {
        static::instance()->where('file_id', '=', $fileId)->delete();
    }

    public static function getByIdentifier($fileId)
    {
        return static::instance()->where('file_id', '=', $fileId)->all();
    }

    public function filterFileId($id)
    {
        return $this->where('file_id', '=', $id);
    }

    public function filterFileIds(array $ids)
    {
        return $this->whereIn('file_id', $ids);
    }

}