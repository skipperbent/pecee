<?php
namespace Pecee;

use Pecee\Handler\ExceptionHandler;
use Pecee\SimpleRouter\RouterBase;
use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    protected static $exceptionHandlers = array();

    public static function start($defaultNamespace = null) {

        Debug::getInstance()->add('Router initialised.');

        // Load framework specific controllers
        self::get('/js/wrap/{files}', 'ControllerJs@wrap')->addSettings(['namespace' => '\Pecee\Controller']);
        self::get('/css/wrap/{files}', 'ControllerCss@wrap')->addSettings(['namespace' => '\Pecee\Controller']);
        self::get('/captcha/show', 'ControllerCaptcha@show')->addSettings(['namespace' => '\Pecee\Controller']);

        // Load routes.php
        $file = $_ENV['basePath'] . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'routes.php';
        if(file_exists($file)) {
            require_once $file;
        }

        // Init locale settings
        Locale::getInstance();

        // Set default namespace
        $defaultNamespace = '\\'.Registry::getInstance()->get('AppName') . '\\Controller';

        // Handle exceptions
        try {
            parent::start($defaultNamespace);
        } catch(\Exception $e) {
            $route = RouterBase::getInstance()->getLoadedRoute();
            /* @var $handler ExceptionHandler */
            foreach(self::$exceptionHandlers as $handler) {
                $class = new $handler();
                $class->handleError(RouterBase::getInstance()->getRequest(), $route, $e);
            }

            throw $e;
        }
    }

    public static function addExceptionHandler($handler) {
        self::$exceptionHandlers[] = $handler;
    }

}