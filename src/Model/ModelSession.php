<?php
namespace Pecee\Model;

use Carbon\Carbon;

class ModelSession extends LegacyModel {

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
		self::nonQuery('DELETE FROM {table} WHERE `time` <= %s', Carbon::createFromTimestamp(time() - (60*30))->toDateTimeString());

		$session = $this->get($this->name);
		if($session->hasRows()) {
			$session->time = Carbon::now()->toDateTimeString();
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