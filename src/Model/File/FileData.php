<?php
namespace Pecee\Model\File;
use Pecee\DB\DBTable;
use Pecee\Model\Model;

class FileData extends Model {
    public function __construct($fileId = null, $key = null, $value = null) {

        $table = new DBTable();
        $table->column('fileId')->string(40)->index();
        $table->column('key')->string(255);
        $table->column('value')->longtext();

        parent::__construct($table);

        $this->fileId = $fileId;
        $this->key = $key;
        $this->value = $value;
    }
    public function save() {
        if(self::Scalar('SELECT `Key` FROM {table} WHERE `key` = %s AND `fileId` = %s', $this->Key, $this->FileID)) {
            parent::update();
        } else {
            parent::save();
        }
    }

    public static function removeAll($fileId) {
        self::nonQuery('DELETE FROM {table} WHERE `fileId` = %s', array($fileId));
    }

    public static function getFileId($fileId) {
        return self::fetchAll('SELECT * FROM {table} WHERE `fileId` = %s', array($fileId));
    }
}