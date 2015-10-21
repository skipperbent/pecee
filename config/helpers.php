<?php

/**
 * Contain helper functions which provides shortcuts for various classes.
 */

function url($controller = null, $parameters = null, $getParams = null)
{
    return \Pecee\Router::getRoute($controller, $parameters, $getParams);
}

function response() {
    return new \Pecee\Http\Response();
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
    return \Pecee\Language::getInstance()->_($key, $args);
}

/**
 * Adds flash message
 *
 * @param $message
 * @param $type
 * @param null $form
 * @param null $placement
 * @param null $index
 */
function message($message, $type, $form = null, $placement = null, $index = null) {
    $msg = new \Pecee\UI\Form\FormMessage();
    $msg->setMessage($message);
    $msg->setForm($form);
    $msg->setIndex($index);
    $msg->setPlacement($placement);

    $message = new \Pecee\Session\SessionMessage();
    $message->set($msg, $type);
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
    return (is_null($value)) ? $default : $value;
}

/**
 * Get current csrf-token
 * @return null|string
 */
function csrf_token() {
    $token = new \Pecee\CsrfToken();
    return $token->getToken();
}