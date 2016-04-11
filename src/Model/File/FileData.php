<?php
namespace Pecee\Model\File;

use Pecee\Model\LegacyModel;

class FileData extends LegacyModel {

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
    public function save() {
        if(self::scalar('SELECT `key` FROM {table} WHERE `key` = %s AND `file_id` = %s', $this->key, $this->file_id)) {
            parent::update();
        } else {
            parent::save();
        }
    }

    public static function removeAll($fileId) {
        self::nonQuery('DELETE FROM {table} WHERE `file_id` = %s', array($fileId));
    }

    public static function getByFileId($fileId) {
        return self::fetchAll('SELECT * FROM {table} WHERE `file_id` = %s', array($fileId));
    }
}