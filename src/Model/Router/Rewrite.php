<?php
namespace Pecee\Model\Router;

use Pecee\Model\Model;

class Rewrite extends Model {

    protected $table = 'rewrite';

	protected $columns = [
		'id',
		'original_url',
		'rewrite_url',
		'host',
		'regex',
		'order'
	];

	public function exists() {
		return (static::where('original_path', '=', $this->original_url)->select(['original_path'])->first() !== null);
	}

	/**
	 * Get rewrite by orig url
	 * @param string $originalUrl
	 * @return static
	 */
	public static function filterOriginalUrl($originalUrl) {
        return static::where('original_url', '=', $originalUrl);
	}

    public static function filterRewritePath($rewriteUrl) {
        return static::where('rewrite_url', '=', $rewriteUrl);
    }

	public static function filterHost($host) {
		return static::where('host', '=', $host);
	}

}