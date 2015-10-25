<?php
$_ENV['base_path'] = substr(get_include_path(), 0, strpos(get_include_path(), PATH_SEPARATOR));
$_ENV['app_name'] = basename($_ENV['base_path']);

$loader = require dirname(dirname($_ENV['base_path'])) . '/vendor/autoload.php';
$loader->addPsr4($_ENV['app_name'] . '\\', $_ENV['base_path'] . 'lib/');

function pecee_autoloader($class) {
    $file = explode('\\', $class);
    $appname = array_shift($file);
    $file = join(DIRECTORY_SEPARATOR, $file) . '.php';

    $modules = \Pecee\Module::getInstance();
    $module = $modules->get($appname);

    if($module) {
        include_once $module . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $file;
    }
}

spl_autoload_register('pecee_autoloader');

// PHP Configuration
ini_set('short_open_tag', 'On');

require_once 'helpers.php';

try {
    // Load .env file
    $dotenv = new \Dotenv\Dotenv($_ENV['base_path']);
    $dotenv->load();
} catch(Exception $e) {
    // Optional
}