<?php
namespace Pecee\Translation\Providers;

use Pecee\Model\Model;
use Pecee\Model\ModelLanguage;
use Pecee\SimpleRouter\Router;

class DbTranslateProvider implements ITranslationProvider {

    /**
     * @var Model
     */
    protected $translations;
    protected $locale;
    protected $defaultLocale;
    protected $autoCreate = true;

    public function __construct() {
        $this->translations = new ModelLanguage();
    }

    public function getContext() {
        $route = Router::getInstance()->getLoadedRoute();

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
            $this->translations = ModelLanguage::getByContext($this->getContext(), $locale);
        }
    }

}