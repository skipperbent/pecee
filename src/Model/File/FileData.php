<?php
namespace Pecee\Model\File;

use Pecee\Model\Model;

class FileData extends Model {

    protected $timestamps = false;
    protected $table = 'file_data';

    protected $columns = [
        'id',
        'file_id',
        'key',
        'value'
    ];

    public function __construct($fileId = null, $key = null, $value = null) {
        parent::__construct();

        $this->file_id = $fileId;
        $this->key = $key;
        $this->value = $value;
    }

    public function exists() {
        if($this->{$this->primary} === null) {
            return false;
        }

        return ($this->where('key', '=', $this->key)->where('file_id', '=', $this->file_id)->first() !== null);
    }

    public static function destroyByFileId($fileId) {
        static::where('file_id', '=', $fileId)->delete();
    }

    public static function getByIdentifier($fileId) {
        return static::where('file_id', '=', $fileId)->all();
    }

}