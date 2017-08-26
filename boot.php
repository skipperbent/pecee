<?php
$_ENV['base_path'] = $abspath;
$_ENV['framework_path'] = __DIR__;

$loader = require $_ENV['base_path'] . '/vendor/autoload.php';

\Pecee\Session\Session::start();

require_once 'helpers.php';

// Load .env file
(new josegonzalez\Dotenv\Loader($_ENV['base_path'] . '/.env'))->parse()->toEnv();

if (env('APP_NAME') !== null) {
    $loader->addPsr4(env('APP_NAME') . '\\', $_ENV['base_path'] . 'app/');
}

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/resources');

request()->app = new Pecee\Application\Application();

if (app()->getDebugEnabled() === true) {
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}