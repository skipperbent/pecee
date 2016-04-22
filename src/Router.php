<?php
namespace Pecee;

use Pecee\Exception\RouterException;
use Pecee\Handler\ExceptionHandler;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\SimpleRouter\RouterBase;
use Pecee\SimpleRouter\SimpleRouter;
use Pecee\UI\Site;

class Router extends SimpleRouter {

    protected static $defaultExceptionHandler;
    protected static $defaultMiddlewares = array();

    public static function start($defaultNamespace = null) {

        Debug::getInstance()->add('Router initialised.');

        // Load framework specific controllers
        static::get('/js-wrap', 'ControllerJs@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.js.wrap');
        static::get('/css-wrap', 'ControllerCss@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.css.wrap');
        static::get('/captcha', 'ControllerCaptcha@show', ['namespace' => '\Pecee\Controller']);

        // Load routes.php
        $file = $_ENV['base_path'] . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'routes.php';
        if(file_exists($file)) {
            require_once $file;
        }

        // Init locale settings
        Locale::getInstance();

        // Set default namespace
        $defaultNamespace = '\\'.$_ENV['app_name'] . '\\Controller';

        // Handle exceptions
        try {

            if(count(static::$defaultMiddlewares)) {
                /* @var $middleware \Pecee\Http\Middleware\IMiddleware */
                foreach(static::$defaultMiddlewares as $middleware) {
                    $middleware = new $middleware();
                    if(!($middleware instanceof IMiddleware)) {
                        throw new RouterException('Middleware must be implement the IMiddleware interface.');
                    }
                    $middleware->handle(RouterBase::getInstance()->getRequest());
                }
            }

            parent::start($defaultNamespace);
        } catch(\Exception $e) {

            $route = RouterBase::getInstance()->getLoadedRoute();

            // Otherwise use the fallback default exceptions handler
            if(static::$defaultExceptionHandler !== null) {
                static::loadExceptionHandler(static::$defaultExceptionHandler, $route, $e);
            }

            throw $e;
        }

        // Output debug info
        if(env('DEBUG', false) && Site::getInstance()->hasAdminIp() && isset($_GET['__debug']) && strtolower($_GET['__debug']) === 'true') {
            echo Debug::getInstance();
        }
    }

    protected static function loadExceptionHandler($class, $route, $e) {
        $class = new $class();

        if(!($class instanceof ExceptionHandler)) {
            throw new \ErrorException('Exception handler must be an instance of \Pecee\Handler\ExceptionHandler');
        }

        $class->handleError(RouterBase::getInstance()->getRequest(), $route, $e);
    }

    public static function defaultExceptionHandler($handler) {
        static::$defaultExceptionHandler = $handler;
    }

    /**
     * Add default middleware that will be loaded before any route
     * @param string|array $middlewares
     */
    public static function defaultMiddleware($middlewares) {
        if(is_array($middlewares)) {
            static::$defaultMiddlewares = $middlewares;
        } else {
            static::$defaultMiddlewares[] = $middlewares;
        }
    }

}