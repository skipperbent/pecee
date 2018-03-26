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
 * @throws \Pecee\Http\Exceptions\MalformedUrlException
 */
function url($name = null, $parameters = null, $getParams = null)
{
    return app()->getUrlHandler()->getUrl($name, $parameters, $getParams);
}

/**
 * @return \Pecee\Http\Response
 * @throws \Pecee\Http\Exceptions\MalformedUrlException
 */
function response()
{
    return Router::response();
}

/**
 * @return \Pecee\Http\Request
 * @throws \Pecee\Http\Exceptions\MalformedUrlException
 */
function request()
{
    return Router::request();
}

/**
 * Get input class
 * @param string|null $index Parameter index name
 * @param string|null $defaultValue Default return value
 * @param string|array|null $methods Default method
 * @return string|\Pecee\Http\Input\Input
 * @throws \Pecee\Http\Exceptions\MalformedUrlException
 */
function input($index = null, $defaultValue = null, $methods = null)
{
    if($index !== null)
    {
        return request()->getInputHandler()->get($index, $defaultValue, $methods);
    }

    return request()->getInputHandler();
}

function redirect($url, $code = null)
{
    if ($code !== null) {
        response()->httpCode($code);
    }

    response()->redirect($url);
}

/**
 * Get main application class
 *
 * @return \Pecee\Application\Application
 * @throws \Pecee\Http\Exceptions\MalformedUrlException
 */
function app()
{
    return request()->app;
}

/**
 * @param string $key
 * @param array|string $args
 * @return string
 * @throws \Pecee\Http\Exceptions\MalformedUrlException
 */
function lang($key, $args = null)
{
    if (is_array($args) === false) {
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
 * @param array|null $args
 * @throws \Pecee\Http\Exceptions\MalformedUrlException
 */
function debug($text, $args = null)
{
    if (app()->getDebugEnabled() === true) {
        if($args !== null && is_array($args) === false)
        {
            $args = func_get_args();
            array_shift($args);
        }

        app()->debug->add($text, $args);
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
 * @throws \Pecee\Http\Exceptions\MalformedUrlException
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
 * Base basename for class
 *
 * @param string $class
 * @return string
 */
function class_basename($class)
{
    $pos = strrpos($class, '\\');
    if ($pos !== false) {
        $class = substr($class, $pos + 1);
    }

    return strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $class));
}