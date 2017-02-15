<?php
namespace Pecee\Translation\Providers;

interface ITranslationProvider
{
    /**
     * Called when looking up single key
     * @param string $key
     * @return string
     */
    public function lookup($key);

    /**
     * Init method
     * @param string $locale
     */
    public function load($locale);
}