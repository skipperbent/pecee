<?php
namespace Pecee\Model\Router;

use Pecee\Model\Model;

class Rewrite extends Model
{
    protected $table = 'rewrite';

    protected $columns = [
        'id',
        'original_url',
        'rewrite_url',
        'host',
        'regex',
        'order',
    ];

    /**
     * @return bool
     * @throws \Pecee\Pixie\Exception
     */
    public function exists()
    {
        return (static::instance()->where('original_path', '=', $this->original_url)->select(['original_path'])->count() > 0);
    }

    /**
     * Get rewrite by orig url
     * @param string $originalUrl
     * @return static
     */
    public function filterOriginalUrl($originalUrl)
    {
        return $this->where('original_url', '=', $originalUrl);
    }

    public function filterRewritePath($rewriteUrl)
    {
        return $this->where('rewrite_url', '=', $rewriteUrl);
    }

    public function filterHost($host)
    {
        return $this->where('host', '=', $host);
    }

}