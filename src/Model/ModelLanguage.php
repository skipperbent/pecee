<?php
namespace Pecee\Model;

use Pecee\DB\PdoHelper;
use Pecee\Locale;
use Pecee\SimpleRouter\RouterBase;

class ModelLanguage extends LegacyModel {

    protected static $instance;

    protected $columns = [
        'id',
        'original',
        'translated',
        'locale',
        'context'
    ];

    /**
     * @return static
     */
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    protected static function getContext() {
        $route = RouterBase::getInstance()->getLoadedRoute();
        if($route && $route->getIdentifier()) {
            return $route->getIdentifier();
        }
        return '';
    }

    public function __construct() {
        parent::__construct();
        $this->context = self::getContext();
    }

    public function lookup($text) {
        if(Locale::getInstance()->getDefaultLocale() != Locale::getInstance()->getLocale() && $this->hasRows()) {

            foreach($this->getRows() as $lang) {
                if(trim($lang->original) == trim($text)) {
                    return $lang->translated;
                }
            }

            // Save new key for translation
            $lang = new static();
            $lang->original = $text;
            $lang->translated = $text;
            $lang->save();
        }
        return $text;
    }

    public static function getPages($rows=15, $page=0) {
        return self::fetchPage('SELECT * FROM {table} GROUP BY `path` ORDER BY `path` ASC', $rows, $page);
    }

    public static function getByContext($context, $locale=null, $rows=null, $page=null) {
        $where=array(sprintf("`context` = '%s'", PdoHelper::escape($context)));
        if(!is_null($locale)) {
            $where[]=sprintf("`locale` = '%s'", PdoHelper::escape($locale));
        }
        return self::fetchPage('SELECT * FROM {table} WHERE ' . join(' && ', $where), $rows, $page);
    }

    public static function getById($id) {
        return self::fetchOne('SELECT * FROM {table} WHERE `id` = %s', $id);
    }
}