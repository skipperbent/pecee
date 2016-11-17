<?php
namespace Pecee\Translation\Providers;

use Pecee\Model\ModelCollection;
use Pecee\Model\ModelLanguage;
use Pecee\SimpleRouter\RouterBase;

class DbTranslateProvider implements ITranslationProvider {

    protected $model;
    /**
     * @var ModelCollection
     */
    protected $translations;
    protected $locale;
    protected $defaultLocale;
    protected $autoCreate = true;

    public function __construct() {
        $this->model = new ModelLanguage();
    }

    public function getContext() {
        $route = RouterBase::getInstance()->getLoadedRoute();

        if($route && $route->getIdentifier()) {
            return $route->getIdentifier();
        }

        return '';
    }

    public function lookup($key) {
        if($this->translations !== null && $this->translations->hasRows()) {

            foreach($this->translations->getRows() as $lang) {
                if(trim($lang->original) == trim($key)) {
                    return $lang->translated;
                }
            }

            if($this->autoCreate) {
                // Save new key for translation
                $lang = new ModelLanguage();
                $lang->original = $key;
                $lang->translated = $key;
                $lang->context = $this->getContext();
                $lang->save();
            }
        }

        return $key;
    }

	public function load($locale, $defaultLocale) {
        if($locale !== $defaultLocale) {
            $this->translations = $this->model->filterLocale($locale)->filterContext($this->getContext())->all();
        }
    }

}