<?php
namespace Pecee\Controller;

abstract class Controller
{

    public function __construct()
    {
        debug('START CONTROLLER ' . get_class($this));
    }

}