<?php
namespace Pecee;

use Pecee\Handler\ExceptionHandler;
use Pecee\SimpleRouter\RouterBase;
use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    protected static $defaultExceptionHandler;

    public static function start($defaultNamespace = null) {

        Debug::getInstance()->add('Router initialised.');

        // Load framework specific controllers
        self::get('/js-wrap/{files}', 'ControllerJs@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.js.wrap')->where(['files' => '[A-Za-z\\-\\.\\,]*?']);
        self::get('/css-wrap/{files}', 'ControllerCss@wrap', ['namespace' => '\Pecee\Controller'])->setAlias('pecee.css.wrap')->where(['files' => '[A-Za-z\\-\\.\\,]*?']);
        self::get('/captcha', 'ControllerCaptcha@show', ['namespace' => '\Pecee\Controller']);

        // Load routes.php
        $file = $_ENV['base_path'] . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'routes.php';
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

            $exceptionHandler = null;

            // Load and use exception-handler defined on group
            if($route && $route->getGroup()) {
                $exceptionHandler = $route->getGroup()->getExceptionHandler();

                if($exceptionHandler !== null) {
                    $class = new $exceptionHandler();
                    $class->handleError(RouterBase::getInstance()->getRequest(), $route, $e);
                }
            }

            // Otherwise use the fallback default exceptions handler
            if(self::$defaultExceptionHandler !== null) {
                $class = new self::$defaultExceptionHandler();
                $class->handleError(RouterBase::getInstance()->getRequest(), $route, $e);
            }

            throw $e;
        }
    }

    public static function setDefaultExceptionHandler(ExceptionHandler $handler) {
        self::$defaultExceptionHandler = $handler;
    }

}