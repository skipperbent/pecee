<?php
namespace Pecee\Translation\Providers;

interface ITranslationProvider {

    public function lookup($key);

}