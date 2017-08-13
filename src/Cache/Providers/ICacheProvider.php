<?php
namespace Pecee\Cache\Providers;

use Carbon\Carbon;

interface ICacheProvider
{

    public function set($key, $value, Carbon $expireDate);

    public function get($key);

    public function remove($key);

    public function clear();

}