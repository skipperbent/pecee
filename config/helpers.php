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
    return new \Pecee\Http\Response();
}

/**
 * @return \Pecee\Http\Request
 */
function request() {
    return \Pecee\Http\Request::getInstance();
}

function redirect($url, $code = null) {
    $response = new \Pecee\Http\Response();

    if($code) {
        $response->httpCode($code);
    }

    return $response->redirect($url);
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