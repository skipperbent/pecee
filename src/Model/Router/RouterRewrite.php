<?php
namespace Pecee\Model\Router;
use Pecee\DB\DBTable;

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
	 * @param string $originalPath
	 * @return \Pecee\Model\ModelRouterRewrite
	 */
	public static function GetByOriginalUrl($originalUrl, $host = null) {
		$where = array('1=1');
		if(!is_null($host)) {
			$where[] = \Pecee\DB\DB::FormatQuery('`host` = %s', array($host));
		}
		return self::FetchOne('SELECT * FROM {table} WHERE `originalUrl` = %s && ' . join(' && ', $where), $originalUrl);
	}
	
	public static function GetByRewritePath($rewriteUrl) {
		return self::FetchOne('SELECT * FROM {table} WHERE `rewriteUrl` = %s', $rewriteUrl);
	}
	
	public static function Get($rows = null, $page = null) {
		return self::FetchPage('SELECT * FROM {table} ORDER BY `order` ASC, `rewriteId` DESC', $rows, $page);
	}
	
	public static function GetByRewriteID($rewriteId) {
		return self::FetchOne('SELECT * FROM {table} WHERE `rewriteId` = %s', $rewriteId);
	}
}