<?php

namespace Pecee\Application;

use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter
{

    public static function init(): void
    {
        if (app()->getDisableFrameworkRoutes() === false) {

            // Load framework specific controllers
            static::get(app()->getCssWrapRouteUrl(), 'ControllerWrap@css', ['namespace' => '\Pecee\Controller'])->setName(app()->getCssWrapRouteName());
            static::get(app()->getJsWrapRouteUrl(), 'ControllerWrap@js', ['namespace' => '\Pecee\Controller'])->setName(app()->getJsWrapRouteName());

        }

        if (env('APP_NAME') !== null) {
            parent::setDefaultNamespace('\\' . env('APP_NAME') . '\\Controllers');
        }
    }

    /**
     * @throws \Pecee\Http\Middleware\Exceptions\TokenMismatchException
     * @throws \Pecee\SimpleRouter\Exceptions\HttpException
     * @throws \Pecee\SimpleRouter\Exceptions\NotFoundHttpException
     * @throws \Exception
     */
    public static function start(): void
    {
        debug('router', 'Router start.');

        parent::start();

        debug('router', 'Router finished.');

        // Output debug info
        if (isset($_GET['__debug']) === true && strtolower($_GET['__debug']) === 'app' && app()->getDebugEnabled() === true) {
            echo app()->debug;
        }
    }

}