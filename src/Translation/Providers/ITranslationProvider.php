<?php
namespace Pecee\Translation\Providers;

interface ITranslationProvider
{
    /**
     * Called when looking up single key
     * @param string $key
     * @return string
     */
    public function lookup(string $key): string;

    /**
     * Init method
     * @param string $locale
     */
    public function load(string $locale): void;
}