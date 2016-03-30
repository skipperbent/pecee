<?php
$_ENV['base_path'] = $abspath;
$_ENV['framework_path'] = __DIR__;

$loader = require $_ENV['base_path'] . '/vendor/autoload.php';

// Load .env file
$dotenv = new \Dotenv\Dotenv($_ENV['base_path']);
$dotenv->load();

require_once 'helpers.php';

$_ENV['app_name'] = env('APP_NAME');
$loader->addPsr4($_ENV['app_name'] . '\\', $_ENV['base_path'] . 'app/');

function pecee_autoloader($class) {
    $file = explode('\\', $class);
    $app = array_shift($file);
    $file = join(DIRECTORY_SEPARATOR, $file) . '.php';

    $modules = \Pecee\Module::getInstance();
    $module = $modules->get($app);

    if($module !== null) {
        require_once $module . $file;
    }
}

spl_autoload_register('pecee_autoloader');