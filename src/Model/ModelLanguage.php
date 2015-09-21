<?php
namespace Pecee\Model;
use Pecee\DB\DBTable;

class ModelLanguage extends \Pecee\Model\Model {

    protected static $instance;

    /**
     * @return self
     */
    public static function getInstance() {
        if(!self::$instance) {
            $locale = strtolower(\Pecee\Locale::getInstance()->getLocale());
            $context = \Pecee\Router::getInstance()->getPath(true);
            $lang = self::GetByContext($context, $locale);
            self::$instance = $lang;

        }
        return self::$instance;
    }

	public function __construct() {

        $table = new DBTable();
        $table->column('languageId')->integer()->primary()->increment();
        $table->column('originalText')->longtext();
        $table->column('translatedText')->longtext();
        $table->column('locale')->string(10)->index();
        //$table->column('pageCode')->string(255)->index();
        $table->column('context')->string(255)->index();

        parent::__construct($table);

        $this->locale = strtolower(\Pecee\Locale::getInstance()->getLocale());
        $this->context = \Pecee\Router::getInstance()->getPath(true);
	}

    public function lookup($text) {
        if(\Pecee\Locale::getInstance()->getDefaultLocale() != \Pecee\Locale::getInstance()->getLocale() && $this->hasRows()) {

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
		return self::FetchPage('SELECT * FROM {table} GROUP BY `path` ORDER BY `path` ASC', $rows, $page);
	}
	
	public static function getByContext($context, $locale=null, $rows=null, $page=null) {
		$where=array(sprintf("`context` = '%s'", \Pecee\DB\DB::Escape($context)));
		if(!is_null($locale)) {
			$where[]=sprintf("`locale` = '%s'", \Pecee\DB\DB::Escape($locale));
		}
		return self::FetchPage('SELECT * FROM {table} WHERE ' . join(' && ', $where), $rows, $page);
	}
	
	public static function getById($languageId) {
		return self::FetchOne('SELECT * FROM {table} WHERE `languageId` = %s', $languageId);
	}
}