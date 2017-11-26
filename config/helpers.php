<?php

/**
 * Contain helper functions which provides shortcuts for various classes.
 */

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
 * This method is an alias for SimpleRouter::getUrl().
 *
 * @see SimpleRouter::getUrl()
 * @param string|null $name
 * @param string|array|null $parameters
 * @param array|null $getParams
 * @return string
 */
function url($name = null, $parameters = null, $getParams = null)
{
	return \Pecee\Router::getUrl($name, $parameters, $getParams);
}

/**
 * @return \Pecee\Http\Response
 */
function response()
{
	return \Pecee\Router::response();
}

/**
 * @return \Pecee\Http\Request
 */
function request()
{
	return \Pecee\Router::request();
}

function redirect($url, $code = null)
{
	if ($code) {
		response()->httpCode($code);
	}

	response()->redirect($url);
}

function lang($key, $args = null)
{
	if (!is_array($args)) {
		$args = func_get_args();
		$args = array_slice($args, 1);
	}

	return request()->translation->translate($key, $args);
}

/**
 * Add debug message.
 * Requires DEBUG=1 to be present in your env file.
 * @param $text
 */
function debug($text)
{
	if (env('DEBUG', false)) {
		request()->debug->add($text);
	}
}

function add_module($name, $path)
{
	if (request()->modules === null) {
		request()->modules = new \Pecee\Modules();
	}

	request()->modules->add($name, $path);
}

/**
 * Get environment variable
 * @param $key
 * @param null $default
 *
 * @return null
 */
function env($key, $default = null)
{
	$value = getenv($key);

	return ($value === false) ? $default : $value;
}

/**
 * Get current csrf-token
 * @return string|null
 */
function csrf_token()
{
    $baseVerifier = Router::router()->getCsrfVerifier();
    if ($baseVerifier !== null) {
        return $baseVerifier->getTokenProvider()->getToken();
    }

    return null;
}

/**
 * Get input class
 * @return \Pecee\Http\Input\Input
 */
function input()
{
	return request()->getInput();
}