<?php
namespace Pecee;

use Pecee\Handler\ExceptionHandler;
use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    protected static $exceptionHandlers = array();

    public static function start() {

        Debug::getInstance()->add('Router initialised.');

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
            /* @var $handler ExceptionHandler */
            foreach(self::$exceptionHandlers as $handler) {
                $class = new $handler();
                $class->handleError($e);
            }

            throw $e;
        }
    }

    public static function redirect($url) {
        // TODO: move to response class
        header('location: ' . $url);
    }

    public static function addExceptionHandler($handler) {
        self::$exceptionHandlers[] = $handler;
    }

}