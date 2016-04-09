<?php
namespace Pecee;

use Pecee\DB\Pdo;
use Pecee\Handler\ExceptionHandler;
use Pecee\SimpleRouter\RouterBase;
use Pecee\SimpleRouter\SimpleRouter;
use Pecee\UI\Site;

class Router extends SimpleRouter {

    protected static $defaultExceptionHandler;

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
            parent::start($defaultNamespace);
        } catch(\Exception $e) {

            $route = RouterBase::getInstance()->getLoadedRoute();

            // Otherwise use the fallback default exceptions handler
            if(self::$defaultExceptionHandler !== null) {
                self::loadExceptionHandler(self::$defaultExceptionHandler, $route, $e);
            }

            throw $e;
        }

        // Output debug info
        if(env('DEBUG', false) && Site::getInstance()->hasAdminIp() && isset($_GET['__debug']) && strtolower($_GET['__debug']) === 'true') {
            echo Debug::getInstance();
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