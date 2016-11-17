<?php
namespace Pecee\Model;

use Pecee\SimpleRouter\RouterBase;

class ModelLanguage extends Model {

    protected $columns = [
        'id',
        'original',
        'translated',
        'locale',
        'context'
    ];

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
        if(request()->locale->getDefaultLocale() !== request()->locale->getLocale() && $this->hasRows()) {

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

    public function filterLocale($locale) {
        return $this->where('locale', '=', $locale);
    }

    public function filterContext($context) {
        return $this->where('context', '=', $context);
    }
}