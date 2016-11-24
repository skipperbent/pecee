<?php
namespace Pecee\DB\Schema;

use Pecee\DB\Pdo;

class Table
{

	const ENGINE_INNODB = 'InnoDB';
	const ENGINE_MEMORY = 'MEMORY';
	const ENGINE_ARCHIVE = 'ARCHIVE';
	const ENGINE_CSV = 'CSV';
	const ENGINE_BLACKHOLE = 'BLACKHOLE';
	const ENGINE_MRG_MYISAM = 'MRG_MYISAM';
	const ENGINE_MYISAM = 'MyISAM';

	public static $ENGINES = [
		self::ENGINE_INNODB,
		self::ENGINE_ARCHIVE,
		self::ENGINE_CSV,
		self::ENGINE_BLACKHOLE,
		self::ENGINE_MEMORY,
		self::ENGINE_MRG_MYISAM,
		self::ENGINE_MYISAM,
	];

	/**
	 * @var array
	 */
	protected $columns;
	protected $name;
	protected $engine;

	public function __construct($name = null)
	{
		$this->name = $name;
		$this->engine = self::ENGINE_INNODB;
	}

	public function name($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @param $name
	 * @return Column
	 */
	public function column($name)
	{
		$column = new Column($this->name);
		$column->setName($name);

		$this->columns[] = $column;

		return $column;
	}

	public function getPrimary($default = null)
	{
		if (count($this->columns) > 0) {
			/* @var $column Column */
			foreach ($this->columns as $column) {
				if ($column->getIndex() == Column::INDEX_PRIMARY) {
					return $column;
				}
			}
		}

		return $default;
	}

	public function getColumnByIndex($index)
	{
		return $this->columns[$index];
	}

	public function getColumnNames($lower = false, $excludePrimary = false)
	{
		$names = [];
		/* @var $column Column */
		foreach ($this->columns as $column) {
			if ($excludePrimary && $column->getIndex() === Column::INDEX_PRIMARY) {
				continue;
			}
			if ($lower) {
				$names[] = strtolower($column->getName());
			} else {
				$names[] = $column->getName();
			}
		}

		return $names;
	}

	public function getColumn($name, $strict = false)
	{
		/* @var $column Column */
		foreach ($this->columns as $column) {
			if (!$strict && strtolower($column->getName()) == strtolower($name) || $strict && $column->getName() == $name) {
				return $column;
			}
		}

		return null;
	}

	public function getColumns()
	{
		return $this->columns;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setEngine($engine)
	{
		if (!in_array($engine, self::$ENGINES)) {
			throw new \InvalidArgumentException('Invalid or unsupported engine');
		}
		$this->engine = $engine;
	}

	public function getEngine()
	{
		return $this->engine;
	}

	public function exists()
	{
		return (Pdo::getInstance()->value('SHOW TABLES LIKE ?', [$this->name]) !== false);
	}

	/**
	 * Create table
	 */
	public function create()
	{
		if (!$this->exists()) {
			$query = [];

			/* @var $column Column */
			foreach ($this->columns as $column) {
				$query[] = $column->getQuery();
			}

			$sql = sprintf('CREATE TABLE `%s` (%s) ENGINE = %s;', $this->name, join(', ', $query), $this->engine);
			Pdo::getInstance()->nonQuery($sql);
		}
	}

	public function alter()
	{
		if ($this->exists()) {

			/* @var $column Column */
			foreach ($this->columns as $column) {
				Pdo::getInstance()->nonQuery(sprintf('ALTER TABLE `%s` CHANGE `%s` %s', $this->name, $column->getName(), $column->getQuery(false)));

				if ($column->getKeyRelationsQuery() !== '') {
					Pdo::getInstance()->nonQuery(sprintf('ALTER TABLE `%s` ADD %s', $this->name, $column->getKeyRelationsQuery()));
				}
			}
		}
	}

	public function rename($name)
	{
		Pdo::getInstance()->nonQuery('RENAME TABLE `' . $this->name . '` TO `' . $name . '`;');
		$this->name = $name;
	}

	public function dropIndex(array $indexes)
	{
		foreach ($indexes as $index) {
			Pdo::getInstance()->nonQuery('ALTER TABLE `' . $this->name . '` DROP INDEX `' . $index . '`');
		}
	}

	public function dropPrimary()
	{
		Pdo::getInstance()->nonQuery('ALTER TABLE `' . $this->name . '` DROP PRIMARY KEY');
	}

	/*public function dropUnique() {

	}*/

	public function dropForeign(array $indexes)
	{
		foreach ($indexes as $index) {
			Pdo::getInstance()->nonQuery('ALTER TABLE `' . $this->name . '` DROP FOREIGN KEY `' . $index . '`');
		}
	}

	public function dropIfExists()
	{
		if ($this->exists()) {
			$this->drop();
		}
	}

	public function drop()
	{
		Pdo::getInstance()->nonQuery('DROP TABLE `' . $this->name . '`;');
	}

}