<?php

/**
 * Contain helper functions which provides shortcuts for various classes.
 */

/**
 * Get url
 *
 * @param string|null $controller
 * @param array|null $parameters
 * @param array|null $getParams
 *
 * @return string
 */
function url($controller = null, array $parameters = null, array $getParams = null) {
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

/**
 * Get input class
 * @return \Pecee\Http\Input\Input
 */
function input() {
    return \Pecee\Http\Request::getInstance()->getInput();
}

function redirect($url, $code = null) {
    $response = new \Pecee\Http\Response();

    if($code) {
        $response->httpCode($code);
    }

    $response->redirect($url);
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
 * @return string|null
 */
function env($key, $default = null) {
    $value = getenv($key);
    return ($value === false) ? $default : $value;
}

/**
 * Get current csrf-token
 * @return string|null
 */
function csrf_token() {
    $baseVerifier = \Pecee\SimpleRouter\RouterBase::getInstance()->getBaseCsrfVerifier();
    if($baseVerifier !== null) {
        return $baseVerifier->getToken();
    }
    return null;
}