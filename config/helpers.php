<?php

/**
 * Contain helper functions which provides shortcuts for various classes.
 */

function url($controller = null, $parameters = null, $getParams = null) {
    return \Pecee\Router::getRoute($controller, $parameters, $getParams);
}

/**
 * @return \Pecee\Http\Response
 */
function response() {
    return \Pecee\Router::response();
}

/**
 * @return \Pecee\Http\Request
 */
function request() {
    return \Pecee\Router::request();
}

function redirect($url, $code = null) {
    if($code) {
        response()->httpCode($code);
    }

    response()->redirect($url);
}

function lang($key, $args = null) {
    if (!is_array($args)) {
        $args = func_get_args();
        $args = array_slice($args, 1);
    }
    return \Pecee\Translation::getInstance()->_($key, $args);
}

/**
 * Get environment variable
 * @param $key
 * @param null $default
 *
 * @return null
 */
function env($key, $default = null) {
    $value = getenv($key);
    return ($value === false) ? $default : $value;
}

/**
 * Get current csrf-token
 * @return null|string
 */
function csrf_token() {
    $token = new \Pecee\CsrfToken();
    return $token->getToken();
}