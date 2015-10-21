<?php
namespace Pecee\Model;
use Pecee\DB\DBTable;
use Pecee\DB\PdoHelper;
use Pecee\Locale;
use Pecee\SimpleRouter\RouterBase;

class ModelLanguage extends Model {

    protected static $instance;

    /**
     * @return self
     */
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    protected static function getContext() {
        $route = RouterBase::getInstance()->getLoadedRoute();
        if($route->getIdentifier()) {
            return $route->getIdentifier();
        }
        return '';
    }

    public function __construct() {

        $table = new DBTable();
        $table->column('languageId')->integer()->primary()->increment();
        $table->column('originalText')->longtext();
        $table->column('translatedText')->longtext();
        $table->column('locale')->string(10)->index();
        $table->column('context')->string(255)->index();

        parent::__construct($table);

        $this->context = self::getContext();
    }

    public function lookup($text) {
        if(Locale::getInstance()->getDefaultLocale() != Locale::getInstance()->getLocale() && $this->hasRows()) {

            foreach($this->getRows() as $lang) {
                if(trim($lang->originalText) == trim($text)) {
                    return $lang->translatedText;
                }
            }

            // Save new key for translation
            $lang = new self();
            $lang->originalText = $text;
            $lang->translatedText = $text;
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

    public static function getById($languageId) {
        return self::fetchOne('SELECT * FROM {table} WHERE `languageId` = %s', $languageId);
    }
}