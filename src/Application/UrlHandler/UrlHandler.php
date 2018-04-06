<?php

namespace Pecee\Application\UrlHandler;

use Pecee\Application\Router;

class UrlHandler implements IUrlHandler
{
    public function getUrl($name = null, $parameters = null, $getParams = null): string
    {
        return Router::getUrl($name, $parameters, $getParams);
    }
}