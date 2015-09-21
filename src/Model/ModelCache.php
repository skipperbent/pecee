<?php
namespace Pecee\Model;
use Pecee\DB\DBTable;

/**
 * Class ModelCache
 * @package Pecee\Model
 */
class ModelCache extends Model {
	public function __construct($key = null, $data = null, $expireDate = null) {

        $table = new DBTable();
        $table->column('key')->string()->primary();
        $table->column('data')->longtext();
        $table->column('expireDate')->datetime()->index();

        parent::__construct($table);

        $this->key = $key;
        if(!is_null($data)) {
            $this->data = serialize($data);
        }

        $this->expireDate = $expireDate;
	}

	/**
	 * Checks expiration date for given cache key.
	 *
	 * @param string $cacheKey
	 * @return bool
	 */
	protected static function IsExpired($expireDate) {
		return ($expireDate && strtotime($expireDate) <= time());
	}

	/**
	 * Clear all cache elements.
	 */
	public static function ClearCache() {
		self::NonQuery('TRUNCATE {table}');
	}

	public static function SetCache($key, $data, $expireMinutes) {
		$expireDate = \Pecee\Date::ToDateTime(time() + ($expireMinutes*60));
		self::RemoveCache($key);
		$model = new self($key, $data, $expireDate);
		return ($model->save());
	}

	public static function RemoveCache($key) {
		self::NonQuery('DELETE FROM {table} WHERE `key` = %s', $key);
	}

	public static function Get($key) {
		$model = self::FetchOne('SELECT * FROM {table} WHERE `key` = %s', $key);
		if($model->hasRow()) {
			if(self::IsExpired($model->getExpireDate())){
				$model->delete();
			} else {
				return unserialize($model->getData());
			}
		}
		return null;
	}
}