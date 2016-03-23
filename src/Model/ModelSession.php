<?php
namespace Pecee\Model;

use Pecee\Date;

class ModelSession extends Model {

    protected $columns = [
        'name',
        'value',
        'time'
    ];

	public function __construct($name = null, $value = null) {

		parent::__construct();

        $this->name = $name;
        $this->value = $value;
	}

	public function save() {
		self::nonQuery('DELETE FROM {table} WHERE `time` <= %s', Date::toDateTime(time()-(60*30)));

		$session = $this->get($this->name);
		if($session->hasRows()) {
			$session->time = Date::toDateTime();
			$session->update();
		} else {
			parent::save();
		}
	}

	/**
	 * Get Session by key
	 * @param string $key
	 * @return static
	 */
	public static function get($key) {
		return self::fetchOne('SELECT * FROM {table} WHERE `name` = %s', $key);
	}
}