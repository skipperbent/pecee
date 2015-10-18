<?php

/* Defines include paths */
function abspath() {
    return substr(__FILE__,0,strlen(__FILE__) - strlen('config/routes.php'));
}

function loadComposer($file) {
    $classmap = (file_exists($file)) ? require $file : array();
    if($classmap && is_array($classmap) && count($classmap) > 0) {
        foreach($classmap as $path) {
            set_include_path(get_include_path() . PATH_SEPARATOR . join(PATH_SEPARATOR, $path));
        }
    }
}

$_ENV['basePath'] = substr(get_include_path(), 0, strpos(get_include_path(), PATH_SEPARATOR));

// Load framework classmape
loadComposer(dirname(__FILE__) . '/../vendor/composer/autoload_psr4.php');

// Composer project classmap
loadComposer(dirname(dirname($_ENV['basePath'])) . '/vendor/composer/autoload_psr4.php');

// Load framework classmape
loadComposer(dirname(__FILE__) . '/../vendor/composer/autoload_namespaces.php');

// Composer project classmap
loadComposer(dirname(dirname($_ENV['basePath'])) . '/vendor/composer/autoload_namespaces.php');

function loadFile($file) {
    if($file) {
        $exists = stream_resolve_include_path($file);
        if($exists !== false) {
            include_once $file;
            return;
        }
    }
}

function __autoload($class) {
    // Try to load composer dependency
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) .'.php';
    loadFile($file);

    $file = null;
    if(strpos($class, 'Pecee\\') !== false) {
        $file = str_replace('Pecee\\', '', $class).'.php';
        $file = abspath() . 'src/' . str_replace('\\', DIRECTORY_SEPARATOR, $file);

    } else {
        $file = explode('\\', $class);

        $appname = array_shift($file);

        $file = join(DIRECTORY_SEPARATOR, $file) . '.php';

        $modules = \Pecee\Module::getInstance();
        $module = $modules->get($appname);

        if($module) {
            $file = $module . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $file;
        }
    }

    loadFile($file);
}

set_include_path(get_include_path() . PATH_SEPARATOR . abspath() . 'src' . DIRECTORY_SEPARATOR . PATH_SEPARATOR . abspath());

// PHP Configuration
ini_set('short_open_tag', 'On');

require_once 'helpers.php';

try {
    // Load .env file
    $dotenv = new \Dotenv\Dotenv($_ENV['basePath']);
    $dotenv->load();
} catch(Exception $e) {
    // Optional
}