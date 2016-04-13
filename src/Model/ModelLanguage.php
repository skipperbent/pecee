<?php
namespace Pecee\Model;

use Pecee\Locale;
use Pecee\SimpleRouter\RouterBase;

class ModelLanguage extends Model {

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
        if(static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
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
        $this->context = static::getContext();
    }

    public function lookup($text) {
        if(Locale::getInstance()->getDefaultLocale() !== Locale::getInstance()->getLocale() && $this->hasRows()) {

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

    public static function filterLocale($locale) {
        return static::where('locale', '=', $locale);
    }

    public static function filterContext($context) {
        return static::where('context', '=', $context);
    }
}