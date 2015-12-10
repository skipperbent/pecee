<?php
namespace Pecee\Model;
use Pecee\Date;
use Pecee\DB\DBTable;
use Pecee\DB\Pdo;

/**
 * Class ModelCache
 * @package Pecee\Model
 */
class ModelCache extends Model {
	public function __construct($key = null, $data = null, $expireDate = null) {

        $table = new DBTable();
        $table->column('key')->string()->primary();
        $table->column('data')->longtext();
        $table->column('expire_date')->datetime()->index();

        parent::__construct($table);

        $this->key = $key;
        if(!is_null($data)) {
            $this->data = serialize($data);
        }

        $this->expire_date = $expireDate;
	}

	/**
	 * Checks expiration date for given cache key.
	 *
	 * @param string $expireDate
	 * @return bool
	 */
	protected static function isExpired($expireDate) {
		return ($expireDate && strtotime($expireDate) <= time());
	}

	/**
	 * Clear all cache elements.
	 */
	public static function clear() {
		Pdo::getInstance()->nonQuery('TRUNCATE {table}');
	}

	public static function set($key, $data, $expireMinutes) {
		$expireDate = Date::ToDateTime(time() + ($expireMinutes*60));
		self::remove($key);
		$model = new static($key, $data, $expireDate);
		return ($model->save());
	}

	public static function remove($key) {
		self::nonQuery('DELETE FROM {table} WHERE `key` = %s', $key);
	}

	public static function get($key) {
		$model = self::fetchOne('SELECT * FROM {table} WHERE `key` = %s', $key);
		if($model->hasRow()) {
			if(self::isExpired($model->expire_date)){
				$model->delete();
			} else {
				return unserialize($model->getData());
			}
		}
		return null;
	}
}