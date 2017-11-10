<?php
namespace Pecee\Cache\Providers;

use Carbon\Carbon;

interface ICacheProvider
{

    public function set($key, $value, Carbon $expireDate, array $keywords = []);

    public function get($key);

    public function getByKeyword(array $keywords = []);

    public function remove($key);

    public function clear(array $keywords = []);

}