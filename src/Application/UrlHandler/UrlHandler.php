<?php

namespace Pecee\Application\UrlHandler;

use Pecee\Application\Router;

class UrlHandler implements IUrlHandler
{
    /**
     * @param string|array|null $name
     * @param string|array|null $parameters
     * @param string|array|null $getParams
     * @return string
     * @throws \Pecee\Exceptions\InvalidArgumentException
     * @throws \Pecee\Http\Exceptions\MalformedUrlException
     */
    public function getUrl($name = null, $parameters = null, $getParams = null)
    {
        return Router::getUrl($name, $parameters, $getParams);
    }
}