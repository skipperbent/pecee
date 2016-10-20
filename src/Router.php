<?php
namespace Pecee;

use Pecee\Session\Session;
use Pecee\SimpleRouter\SimpleRouter;
use Pecee\UI\Site;

class Router extends SimpleRouter {

    public static function start($defaultNamespace = null) {

        Debug::getInstance()->add('Router initialised.');

        Session::start();

        // Load framework specific controllers
        static::get('/js-wrap', 'ControllerJs@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.js.wrap');
        static::get('/css-wrap', 'ControllerCss@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.css.wrap');
        static::get('/captcha', 'ControllerCaptcha@show', ['namespace' => '\Pecee\Controller']);

        // Load routes.php

        require_once $_ENV['base_path'] . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'routes.php';

        // Init locale settings
        Locale::getInstance();

        parent::start('\\'.$_ENV['app_name'] . '\\Controller');

        // Output debug info
        if(env('DEBUG', false) && Site::getInstance()->hasAdminIp() && isset($_GET['__debug']) && strtolower($_GET['__debug']) === 'true') {
            echo Debug::getInstance();
        }
    }

}