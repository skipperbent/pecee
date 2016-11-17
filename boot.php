<?php
$_ENV['base_path'] = $abspath;
$_ENV['framework_path'] = __DIR__;

$loader = require $_ENV['base_path'] . '/vendor/autoload.php';

require_once 'helpers.php';

// Load .env file
(new josegonzalez\Dotenv\Loader($_ENV['base_path'] . '/.env'))->parse()->toEnv();

$loader->addPsr4(env('APP_NAME') . '\\', $_ENV['base_path'] . 'app/');

// Locale
request()->locale = new \Pecee\Locale();
request()->translation = new \Pecee\Translation\Translation();
request()->site = new \Pecee\UI\Site();
request()->debug = new \Pecee\Debug();

debug('Framework initialised');