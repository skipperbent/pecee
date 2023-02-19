<?php
namespace Pecee\Controller;

abstract class Controller
{

    public function __construct()
    {
        debug('controller', 'Start %s', static::class);
    }

}