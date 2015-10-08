<?php

/**
 * Contain helper functions which provides shortcuts for various classes.
 */

function url($controller = null, $parameters = null, $getParams = null) {
    return \Pecee\Router::getRoute($controller, $parameters, $getParams);
}

function redirect($url) {
    return \Pecee\Router::redirect($url);
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

    \Pecee\Session\SessionMessage::getInstance()->set($msg, $type);
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