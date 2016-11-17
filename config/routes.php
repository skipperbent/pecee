<?php
$_ENV['base_path'] = substr(get_include_path(), 0, strpos(get_include_path(), PATH_SEPARATOR));
$_ENV['app_name'] = basename($_ENV['base_path']);
$_ENV['framework_path'] = dirname(__DIR__);

$loader = require dirname(dirname($_ENV['base_path'])) . '/vendor/autoload.php';
$loader->addPsr4($_ENV['app_name'] . '\\', $_ENV['base_path'] . 'lib/');

// Load .env file
$dotenv = new \Dotenv\Dotenv($_ENV['base_path']);
$dotenv->load();

require_once 'helpers.php';

// Locale
request()->locale = new \Pecee\Locale();
request()->translation = new \Pecee\Translation\Translation();
request()->site = new \Pecee\UI\Site();

// Debugger
request()->debug = new \Pecee\Debug();
debug('Framework loaded');

function modules_autoloader($class) {
    $file = explode('\\', $class);
    $app = array_shift($file);
    $file = join(DIRECTORY_SEPARATOR, $file) . '.php';

    if(request()->modules !== null && request()->modules instanceof \Pecee\Modules) {
        $module = request()->modules->get($app);

        if ($module !== null) {
            require_once $module . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $file;
        }
    }
}

if(request()->modules !== null && request()->modules instanceof \Pecee\Modules && count(request()->modules->getList())) {
    foreach(request()->modules as $module) {
        set_include_path(get_include_path() . PATH_SEPARATOR . $module . '/lib');
    }
}

spl_autoload_register('modules_autoloader');