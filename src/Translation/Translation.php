<?php

namespace Pecee\Translation;

use Pecee\Translation\Providers\ITranslationProvider;

class Translation
{
    protected $provider;

    /**
     * Translate message.
     *
     * @param string $key
     * @param array ...$args
     * @return string
     */
    public function _(string $key, ...$args)
    {
        return vsprintf($this->lookup($key), ...$args);
    }

    /**
     * Translate message.
     * @param string $key
     * @param array ...$args
     * @return string
     */
    public function translate(string $key, ...$args)
    {
        return vsprintf($this->lookup($key), ...$args);
    }

    protected function lookup(string $key)
    {
        if ($this->provider instanceof ITranslationProvider) {
            return $this->provider->lookup($key);
        }

        return $key;
    }

    public function setProvider(ITranslationProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return ITranslationProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }
}