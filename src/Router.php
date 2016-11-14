<?php
namespace Pecee;

use Pecee\DB\Pdo;
use Pecee\Handler\ExceptionHandler;
use Pecee\SimpleRouter\RouterBase;
use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    protected static $defaultExceptionHandler;

    public static function start($defaultNamespace = null) {

        debug('Router initialised.');

        // Load framework specific controllers
        static::get('/js-wrap', 'ControllerJs@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.js.wrap');
        static::get('/css-wrap', 'ControllerCss@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.css.wrap');

        // Load routes.php
        $file = $_ENV['base_path'] . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'routes.php';
        if(file_exists($file)) {
            require_once $file;
        }

        // Set default namespace
        $defaultNamespace = '\\'.$_ENV['app_name'] . '\\Controller';

        // Handle exceptions
        try {
            parent::start($defaultNamespace);
        } catch(\Exception $e) {

            $route = RouterBase::getInstance()->getLoadedRoute();

            $exceptionHandler = null;

            // Load and use exception-handler defined on group
            if($route && $route->getGroup()) {
                $exceptionHandler = $route->getGroup()->getExceptionHandler();

                if($exceptionHandler !== null) {
                    self::loadExceptionHandler($exceptionHandler, $route, $e);
                }
            }

            // Otherwise use the fallback default exceptions handler
            if(self::$defaultExceptionHandler !== null) {
                self::loadExceptionHandler(self::$defaultExceptionHandler, $route, $e);
            }

            throw $e;
        }

        // Output debug info
        if(env('DEBUG', false) && request()->site->hasAdminIp() && isset($_GET['__debug']) && strtolower($_GET['__debug']) === 'true') {
            echo request()->debug;
        }

        Pdo::getInstance()->close();
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

}