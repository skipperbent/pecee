<?php
namespace Pecee\Model\Router;
use Pecee\DB\DBTable;
use Pecee\Db\PdoHelper;
use Pecee\Model\Model;

class RouterRewrite extends Model {
	public function __construct() {

        $table = new DBTable();
        $table->column('rewriteId')->integer()->primary()->increment();
        $table->column('originalUrl')->string(355)->index();
        $table->column('rewriteUrl')->string(355)->index();
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
		return self::fetchOne('SELECT * FROM {table} WHERE `originalUrl` = %s && ' . join(' && ', $where), $originalUrl);
	}
	
	public static function getByRewritePath($rewriteUrl) {
		return self::fetchOne('SELECT * FROM {table} WHERE `rewriteUrl` = %s', $rewriteUrl);
	}
	
	public static function get($rows = null, $page = null) {
		return self::fetchPage('SELECT * FROM {table} ORDER BY `order` ASC, `rewriteId` DESC', $rows, $page);
	}
	
	public static function getByRewriteId($rewriteId) {
		return self::fetchOne('SELECT * FROM {table} WHERE `rewriteId` = %s', $rewriteId);
	}
}