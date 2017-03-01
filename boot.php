<?php
$_ENV['base_path'] = $abspath;
$_ENV['framework_path'] = __DIR__;

$loader = require $_ENV['base_path'] . '/vendor/autoload.php';

require_once 'helpers.php';

// Load .env file
(new josegonzalez\Dotenv\Loader($_ENV['base_path'] . '/.env'))->parse()->toEnv();

$loader->addPsr4(env('APP_NAME') . '\\', $_ENV['base_path'] . 'app/');

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/resources');

request()->app = new Pecee\Application\Application();

if (app()->getDebugEnabled() === true) {
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}

if (PHP_SAPI === 'cli') {
    /* Load routes so url() can be used in cli-mode */
    \Pecee\Application\Router::init();
    \Pecee\Application\Router::router()->loadRoutes();
}