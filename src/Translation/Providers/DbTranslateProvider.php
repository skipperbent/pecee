<?php
namespace Pecee\Translation\Providers;

use Pecee\Model\ModelLanguage;

class DbTranslateProvider implements ITranslationProvider {

    protected $model;

    public function __construct() {
        $this->model = new ModelLanguage();
    }

    public function lookup($key) {
		return $this->model->lookup($key);
	}

}