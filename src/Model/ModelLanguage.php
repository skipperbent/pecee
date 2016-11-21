<?php
namespace Pecee\Model;

use Pecee\DB\PdoHelper;

class ModelLanguage extends Model {

    protected $columns = [
        'id',
        'original',
        'translated',
        'locale',
        'context'
    ];

    public static function getPages($rows = 15, $page = 0) {
        return self::fetchPage('SELECT * FROM {table} GROUP BY `path` ORDER BY `path` ASC', $rows, $page);
    }

    public static function getByContext($context, $locale = null, $rows = null, $page = null) {
        $where = array(sprintf("`context` = '%s'", PdoHelper::escape($context)));

        if(!is_null($locale)) {
            $where[] = sprintf("`locale` = '%s'", PdoHelper::escape($locale));
        }

        return self::fetchPage('SELECT * FROM {table} WHERE ' . join(' && ', $where), $rows, $page);
    }

    public static function getById($id) {
        return self::fetchOne('SELECT * FROM {table} WHERE `id` = %s', $id);
    }
}