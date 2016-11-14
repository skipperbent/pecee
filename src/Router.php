<?php
namespace Pecee;

use Pecee\Session\Session;
use Pecee\SimpleRouter\SimpleRouter;
use Pecee\UI\Site;

class Router extends SimpleRouter {

    public static function start($defaultNamespace = null) {

        debug('Router initialised.');

        Session::start();

        // Load framework specific controllers
        static::get('/js-wrap', 'ControllerJs@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.js.wrap');
        static::get('/css-wrap', 'ControllerCss@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.css.wrap');

        // Load routes.php

        require_once $_ENV['base_path'] . 'app' . DIRECTORY_SEPARATOR . 'routes.php';

        parent::start('\\'.$_ENV['app_name'] . '\\Controller');

        // Output debug info
        if(env('DEBUG', false) && request()->site->hasAdminIp() && isset($_GET['__debug']) && strtolower($_GET['__debug']) === 'true') {
            echo request()->debug;
        }
    }

}