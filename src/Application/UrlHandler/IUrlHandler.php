<?php

namespace Pecee\Application\UrlHandler;

interface IUrlHandler
{

    public function getUrl($name = null, $parameters = null, $getParams = null);

}