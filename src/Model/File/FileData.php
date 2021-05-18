<?php

namespace Pecee\Model\File;

use Pecee\Model\Model;

class FileData extends Model
{
    protected $timestamps = false;
    protected $table = 'file_data';

    protected $columns = [
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

    public function exists()
    {
        if ($this->{$this->primaryKey} === null) {
            return false;
        }

        return ($this->where('key', '=', $this->key)->where('file_id', '=', $this->file_id)->first() !== null);
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