<?php

namespace Pecee\Cache\Providers;

use Carbon\Carbon;

abstract class CacheProvider implements ICacheProvider
{

    public function getOrSet($key, \Closure $callback, Carbon $expireDate, array $keywords = []): ICacheProvider
    {
        $item = $this->get($key);

        if ($item === null) {
            $item = $callback();
            if ($item !== null) {
                $this->set($key, $item, $expireDate, $keywords);
            }
        }

        return $item;
    }

}