<?php
namespace Pecee\Cache\Providers;

use Carbon\Carbon;

interface ICacheProvider
{

    public function set(string $key, $value, Carbon $expireDate, array $keywords = []);

    public function get(string $key);

    public function getByKeyword(array $keywords = []);

    public function remove(string $key);

    public function clear(array $keywords = []);

}