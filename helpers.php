<?php

/**
 * Contain helper functions which provides shortcuts for various classes.
 */

use Pecee\Application\Router as Router;

/**
 * Get url for a route by using either name/alias, class or method name.
 *
 * The name parameter supports the following values:
 * - Route name
 * - Controller/resource name (with or without method)
 * - Controller class name
 *
 * When searching for controller/resource by name, you can use this syntax "route.name@method".
 * You can also use the same syntax when searching for a specific controller-class "MyController@home".
 * If no arguments is specified, it will return the url for the current loaded route.
 *
 * @param string|null $name
 * @param string|array|null $parameters
 * @param array|null $getParams
 * @return string
 */
function url($name = null, $parameters = null, $getParams = null)
{
	return Router::getUrl($name, $parameters, $getParams);
}

/**
 * @return \Pecee\Http\Response
 */
function response()
{
	return Router::response();
}

/**
 * @return \Pecee\Http\Request
 */
function request()
{
	return Router::request();
}

/**
 * Get input class
 * @return \Pecee\Http\Input\Input
 */
function input()
{
	return request()->getInput();
}

function redirect($url, $code = null)
{
	if ($code) {
		response()->httpCode($code);
	}

	response()->redirect($url);
}

/**
 * Get main application class
 *
 * @return \Pecee\Application\Application
 */
function app()
{
	return request()->app;
}

function lang($key, $args = null)
{
	if (!is_array($args)) {
		$args = func_get_args();
		$args = array_slice($args, 1);
	}

	return app()->translation->translate($key, $args);
}

/**
 * Add debug message.
 * Requires DEBUG=1 to be present in your env file.
 *
 * @param string $text
 */
function debug($text)
{
	if (env('DEBUG', false)) {
		app()->debug->add($text);
	}
}

function add_module($name, $path)
{
	app()->addModule($name, $path);
}

/**
 * Get environment variable
 * @param string $key
 * @param null $default
 *
 * @return string|null
 */
function env($key, $default = null)
{
	return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}

/**
 * Get current csrf-token
 * @return string|null
 */
function csrf_token()
{
	$baseVerifier = Router::router()->getInstance()->getCsrfVerifier();
	if ($baseVerifier !== null) {
		return $baseVerifier->getToken();
	}

	return null;
}