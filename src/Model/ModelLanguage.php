<?php
namespace Pecee\Model;

class ModelLanguage extends Model
{

    protected $table = 'language';

    protected $columns = [
        'id',
        'original',
        'translated',
        'locale',
        'context',
    ];

    public function filterLocale($locale)
    {
        return $this->where('locale', '=', $locale);
    }

    public function filterContext($context)
    {
        return $this->where('context', '=', $context);
    }
}