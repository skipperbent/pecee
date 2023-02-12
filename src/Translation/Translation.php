<?php

namespace Pecee\Translation;

use Pecee\Translation\Providers\ITranslationProvider;

class Translation
{
    protected ?ITranslationProvider $provider = null;

    /**
     * Translate message.
     *
     * @param string $key
     * @param array ...$args
     * @return string
     */
    public function _(string $key, ...$args): string
    {
        return vsprintf($this->lookup($key), ...$args);
    }

    /**
     * Translate message.
     * @param string $key
     * @param array ...$args
     * @return string
     */
    public function translate(string $key, ...$args): string
    {
        return vsprintf($this->lookup($key), $args);
    }

    protected function lookup(string $key): string
    {
        if ($this->provider instanceof ITranslationProvider) {
            return $this->provider->lookup($key);
        }

        return $key;
    }

    public function setProvider(ITranslationProvider $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * @return ITranslationProvider
     */
    public function getProvider(): ?ITranslationProvider
    {
        return $this->provider;
    }
}