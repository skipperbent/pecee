<?php

$_ENV['framework_path'] = dirname(__DIR__);

function pecee_autoloader($class) {
    $file = explode('\\', $class);
    $appname = array_shift($file);
    $file = join(DIRECTORY_SEPARATOR, $file) . '.php';

    $modules = \Pecee\Module::getInstance();
    $module = $modules->get($appname);

    if($module !== null) {
        require_once $module . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $file;
    }
}

spl_autoload_register('pecee_autoloader');

require_once 'helpers.php';