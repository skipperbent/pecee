<?php
namespace Pecee\Application;

use Pecee\Session\Session;
use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter
{

    public static function start()
    {

        debug('Router initialised.');

        Session::start();

        if (app()->getDisableFrameworkRoutes() === false) {

            // Load framework specific controllers
            static::get(app()->getCssWrapRouteUrl(), 'ControllerWrap@css', ['namespace' => '\Pecee\Controller'])->setName(app()->getCssWrapRouteName());
            static::get(app()->getJsWrapRouteUrl(), 'ControllerWrap@js', ['namespace' => '\Pecee\Controller'])->setName(app()->getJsWrapRouteName());

        }

        // Load routes.php
        require_once env('base_path') . 'routes' . DIRECTORY_SEPARATOR . 'web.php';

        parent::setDefaultNamespace('\\' . env('APP_NAME') . '\\Controller');
        parent::start();

        // Output debug info
        if (isset($_GET['__debug']) && env('DEBUG', false) === true && strtolower($_GET['__debug']) === 'true' && app()->hasAdminIp()) {
            echo app()->debug;
        }
    }

}