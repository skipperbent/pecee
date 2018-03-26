<?php

namespace Pecee\Application;

use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter
{

    public static function init()
    {
        if (app()->getDisableFrameworkRoutes() === false) {

            // Load framework specific controllers
            static::get(app()->getCssWrapRouteUrl(), 'ControllerWrap@css', ['namespace' => '\Pecee\Controller'])->setName(app()->getCssWrapRouteName());
            static::get(app()->getJsWrapRouteUrl(), 'ControllerWrap@js', ['namespace' => '\Pecee\Controller'])->setName(app()->getJsWrapRouteName());

        }

        if (env('APP_NAME') !== null) {
            parent::setDefaultNamespace('\\' . env('APP_NAME') . '\\Controller');
        }
    }

    public static function start() : void
    {
        debug('Router initialised.');

        parent::start();

        // Output debug info

        if (isset($_GET['__debug']) && strtolower($_GET['__debug']) === 'app' && app()->getDebugEnabled() === true && app()->hasAdminIp()) {
            echo app()->debug;
        }
    }

}