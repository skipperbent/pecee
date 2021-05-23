<?php

/**
 * Contain helper functions which provides shortcuts for various classes.
 */

use Pecee\Application\Router;
use Pecee\Http\Request;
use Pecee\Http\Response;
use Pecee\Http\Url;

request()->app = new \Pecee\Application\Application();

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
 * @return \Pecee\Http\Url
 * @throws \InvalidArgumentException
 */
function url(?string $name = null, $parameters = null, ?array $getParams = null): Url
{
    return Router::getUrl($name, $parameters, $getParams);
}

/**
 * @return \Pecee\Http\Response
 */
function response(): Response
{
    return Router::response();
}

/**
 * @return \Pecee\Http\Request
 */
function request(): Request
{
    return Router::request();
}

/**
 * Get input class
 * @param string|null $index Parameter index name
 * @param string|null $defaultValue Default return value
 * @param array ...$methods Default methods
 * @return \Pecee\Http\Input\InputHandler|array|string
 */
function input($index = null, $defaultValue = null, ...$methods)
{
    if ($index !== null) {
        return request()->getInputHandler()->value($index, $defaultValue, ...$methods);
    }

    return request()->getInputHandler();
}

/**
 * @param string $url
 * @param int|null $code
 */
function redirect(string $url, ?int $code = null): void
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
 */
function app()
{
    return request()->app;
}

/**
 * @param string $key
 * @param string|array ...$args
 * @return string
 */
function lang($key, ...$args): string
{
    return app()->translation->translate($key, ...$args);
}

/**
 * Add debug message.
 * Requires DEBUG=1 to be present in your env file.
 *
 * @param string $text
 * @param array ...$args
 */
function debug(string $text, ...$args): void
{
    if (app()->getDebugEnabled() === true) {
        app()->debug->add($text, ...$args);
    }
}

function add_module(string $name, string $path): void
{
    app()->addModule($name, $path);
}

/**
 * Get environment variable
 * @param string $key
 * @param string|null $default
 *
 * @return string|null
 */
function env(string $key, ?string $default = null): ?string
{
    return $_ENV[$key] ?? $default;
}

/**
 * Get current csrf-token
 * @return string|null
 */
function csrf_token(): ?string
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
function class_basename(string $class): string
{
    $pos = strrpos($class, '\\');
    if ($pos !== false) {
        $class = substr($class, $pos + 1);
    }

    return strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $class));
}