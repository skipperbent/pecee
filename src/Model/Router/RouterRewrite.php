<?php
namespace Pecee\Model\Router;
use Pecee\DB\DBTable;
use Pecee\DB\PdoHelper;
use Pecee\Model\Model;

class RouterRewrite extends Model {
	public function __construct() {

        $table = new DBTable();
        $table->column('id')->integer()->primary()->increment();
        $table->column('original_url')->string(355)->index();
        $table->column('rewrite_url')->string(355)->index();
        $table->column('host')->string(255)->index();
        $table->column('regex')->string(255)->index();
        $table->column('order')->integer()->index();

        parent::__construct($table);
	}

	public function exists() {
		return (self::Scalar('SELECT `originalPath` FROM {table} WHERE `originalPath` = %s', $this->OriginalPath));
	}

	/**
	 * Get rewrite by originalpath
	 * @param string $originalUrl
	 * @param string|null $host
	 * @return \Pecee\Model\ModelRouterRewrite
	 */
	public static function getByOriginalUrl($originalUrl, $host = null) {
		$where = array('1=1');
		if(!is_null($host)) {
			$where[] = PdoHelper::formatQuery('`host` = %s', array($host));
		}
		return self::fetchOne('SELECT * FROM {table} WHERE `original_url` = %s && ' . join(' && ', $where), $originalUrl);
	}

	public static function getByRewritePath($rewriteUrl) {
		return self::fetchOne('SELECT * FROM {table} WHERE `rewrite_url` = %s', $rewriteUrl);
	}

	public static function get($rows = null, $page = null) {
		return self::fetchPage('SELECT * FROM {table} ORDER BY `order` ASC, `id` DESC', $rows, $page);
	}

	public static function getById($id) {
		return self::fetchOne('SELECT * FROM {table} WHERE `id` = %s', $id);
	}
}