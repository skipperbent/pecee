<?php
namespace Pecee\DB;
use Pecee\Debug;
use Pecee\Integer;
use Pecee\Collection;
use Pecee\Registry;

class DB {
	private static $instance;
	/**
	 * Mysqli instance
	 * @var \mysqli
	 */
	protected $connection;
	protected $sql;
	protected $charset = null;

	/* CHARSETS */
	const CHARSET_DEFAULT='';
	const CHARSET_UTF8 = 'utf8';

	/* Mysql data types
	const DATATYPE_DECIMAL = 0;
	const DATATYPE_TINY = 0;
	const DATATYPE_SHORT = 1;
	const DATATYPE_LONG = 2;
	const DATATYPE_FLOAT = 3;
	const DATATYPE_DOUBLE = 5;
	const DATATYPE_null = 6;
	const DATATYPE_TIMESTAMP = 7;
	const DATATYPE_LONGLONG = 8;
	const DATATYPE_INT24 = 9;
	const DATATYPE_DATE = 10;
	const DATATYPE_TIME = 11;
	const DATATYPE_DATETIME = 12;
	const DATATYPE_YEAR = 13;
	const DATATYPE_NEWDATE = 14;
	const DATATYPE_ENUM = 247;
	const DATATYPE_SET = 248;
	const DATATYPE_TINY_BLOB = 249;
	const DATATYPE_MEDIUM_BLOB = 250;
	const DATATYPE_LONG_BLOB = 251;
	const DATATYPE_BLOB = 252;
	const DATATYPE_VAR_STRING = 253;
	const DATATYPE_STRING = 254;
	const DATATYPE_GEOMETRY = 255;*/

	const SETTINGS_HOST = 'DBHost';
	const SETTINGS_USERNAME = 'DBUsername';
	const SETTINGS_PASSWORD = 'DBPassword';
	const SETTINGS_DATABASE = 'DBDatabase';

	protected $host;
	protected $username;
	protected $password;
	protected $database;

	/**
	 * Returns new instance
	 * @return self
	 */
	public static function getInstance() {
		if(!self::$instance) {
			$registry = Registry::getInstance();
			self::$instance = new self($registry->Get('DBHost'),
				$registry->Get('DBUsername'),
				$registry->Get('DBPassword'),
				$registry->Get('DBDatabase'));
		}
		return self::$instance;
	}

	public function __construct($host,$username,$password,$db) {
		$this->host=$host;
		$this->username=$username;
		$this->password=$password;
		$this->database=$db;
		$this->connect();
	}

	public function __destruct() {
		$this->dispose();
	}

	protected function connect() {
		if(!$this->connection instanceof \mysqli){
			$this->connection = @new \mysqli($this->host, $this->username, $this->password, $this->database);
			if($this->connection->connect_error) {
				throw new DBException($this->connection->connect_error,
					$this->connection->connect_errno,
					$this->sql);
			}
			if($this->charset != null) {
				$this->connection->set_charset($this->charset);
			}
		}
	}

	/**
	 * Close mysql connection.
	 */
	public function dispose(){
		if($this->connection instanceof \mysqli) {
			$this->connection->close();
			$this->connection=null;
		}
	}

	/**
	 * Counts fieldname in the database, giving the
	 * number of rows in the table with the specified fieldname.
	 *
	 * @param string $fieldName
	 * @param string $tableName
	 * @param string $where
	 * @param array|null $args
	 * @return int
	 */
	public function count($fieldName, $tableName, $where = '', $args = null) {
		$args = (is_null($args) || is_array($args) ? $args : self::ParseArgs(func_get_args(), 3));
		$where = self::FormatQuery($where, $args);
		$q = $this->scalar(sprintf('SELECT COUNT(%s) FROM %s %s',$fieldName,$tableName,$where,$fieldName));
		return ($q==null||!Integer::isInteger($q)) ? 0 : $q;
	}

	/**
	 * Returns maximum rows by given fieldname.
	 *
	 * @param string $fieldName
	 * @param string $tableName
	 * @param string $where
	 * @param array|null $args
	 * @return int
	 */
	public function max($fieldName, $tableName, $where = '', $args = null) {
		$args = (is_null($args) || is_array($args) ? $args : self::ParseArgs(func_get_args(), 3));
		$where = self::FormatQuery($where, $args);
		$q = $this->scalar(sprintf('SELECT MAX(%s) FROM %s %s',$fieldName,$tableName,$where));
		return ($q==null||!Integer::isInteger($q)) ? 0 : $q;
	}

	public function query($query, $pageIndex = null, $pageSize = null, $args = null) {
		Debug::getInstance()->add('START SQL-QUERY:<br/>' . $query);
		if(!is_array($args) && !is_null($args)) {
			$args = func_get_args();
			$args = array_slice($args, 3);
		}
		if(!is_null($pageIndex) && !is_null($pageSize)){
			$query .= sprintf(' LIMIT %s, %s',($pageIndex*$pageSize), $pageSize);
		}

		$this->sql = self::FormatQuery($query, $args);
		$q = $this->connection->query($this->sql);
		if($this->connection->error) {
			$code = $this->connection->errno;
			throw new DBException($this->connection->error,$code, $this->sql);
		}
		Debug::getInstance()->add('<div style="padding-left:20px;color:#999;">END SQL-QUERY</div>');
		return $q;
	}

	public function insert($query, $args = null) {
		$args = (is_null($args) || is_array($args) ? $args : self::ParseArgs(func_get_args(), 1));
		$this->query($query, null, null, $args);
		$id = $this->connection->insert_id;
		return $id;
	}

	public static function Escape($value,$escapeSprintf=true) {
		$str=self::getInstance()->getConnection()->escape_string($value);
		if($escapeSprintf) {
			$str=str_replace('%', '%%', $str);
		}
		return $str;
	}

	/**
	 * Escapes query and formats it with arguments
	 * @param string $query
	 * @param array|null $args
	 * @return string
	 */
	public static function FormatQuery($query, $args=null) {
		if(is_array($args) && count($args) > 0) {
			$a=array();
			foreach($args as $arg) {
				if(is_null($arg)) {
					$a[] =  'null';
				} elseif(Integer::isInteger($arg)) {
					$a[] =  sprintf("%s", self::getInstance()->getConnection()->escape_string($arg));
				} else {
					$a[] =  sprintf("'%s'", self::getInstance()->getConnection()->escape_string($arg));
				}
			}
			if(count($a) > 0 && $query) {
				return vsprintf($query, $a);
			}
		}
		return $query;
	}

	public static function ParseArgs($args, $offset) {
		if(is_array($args) && count($args) > $offset) {
			return array_slice($args, $offset);
		}
		return $args;
	}

	public static function JoinArray(array $array, $isFields=false) {
		$statement = array();
		foreach($array as $arr) {
			$statement[] = (($isFields) ? '`' : "'") . self::Escape($arr) . (($isFields) ? '`' : "'");
		}
		return join(',', $statement);
	}

	public static function JoinValues(array $array, $Delimiter=', ', $Value = '%s') {
		$output = '';
		for($i=0;$i<count($array);$i++) {
			$output .= self::getInstance()->getConnection()->escape_string($Value).(($i+1 < count($array)) ? $Delimiter : '');
		}
		return $output;
	}

	public function listTable($query, $args=null) {
		$args = (is_null($args) || is_array($args) ? $args : self::ParseArgs(func_get_args(), 1));
		$q = $this->query($query, null, null, $args);
		if($q->num_rows >= 1) {
			$row = $q->fetch_assoc();
			if($row) {
				$q->free();
				return new Collection($row);
			}
		}
		return null;
	}

	public function listArray($query, $PageIndex = null, $PageSize = null, $args = null) {
		$args = (is_null($args) || is_array($args) ? $args : self::ParseArgs(func_get_args(), 3));
		$q = $this->query($query, $PageIndex, $PageSize, $args);
		if($q) {
			$items = array();
			while(($row = $q->fetch_assoc()) != false) {
				$items[] = $row;
			}
			$q->free();
			return $items;
		}
		return null;
	}

	public function listTableArray( $query, $PageIndex = null, $PageSize = null, $args = null ) {
		$args = (is_null($args) || is_array($args) ? $args : self::ParseArgs(func_get_args(), 3));
		$q = $this->query($query, $PageIndex, $PageSize, $args);
		if($q) {
			$items = array();
			while(($row = $q->fetch_assoc()) != false) {
				$items[] = new Collection($row);
			}
			$q->free();
			return $items;
		}
		return null;
	}

	public function scalar($query, $args = null) {
		$args = (is_null($args) || is_array($args) ? $args : self::ParseArgs(func_get_args(), 1));
		$q = $this->query($query, null, null, $args);
		if(isset($q->num_rows) && $q->num_rows > 0) {
			$r = $q->fetch_row();
			return $r[0];
		}
		return null;
	}

	public function affectedRows($query, $args = null) {
		$args = (is_null($args) || is_array($args) ? $args : self::ParseArgs(func_get_args(), 1));
		$this->query($query, null, null, $args);
		$r = (isset($this->connection->affected_rows)) ? $this->connection->affected_rows : 0;
		return $r;
	}

	/*
	 * Executes a query.
	 */
	public function nonQuery($query, $args = null) {
		$args = (is_null($args) || is_array($args) ? $args : self::ParseArgs(func_get_args(), 1));
		$this->query($query, null, null, $args);
	}

	/**
	 * Returns mysqli connection
	 * @return \mysqli
	 */
	public function getConnection() {
		return $this->connection;
	}

	public function setConnection($connection) {
		$this->connection = $connection;
	}

	public function setCharset($charset) {
		$this->charset=$charset;
	}

	public function runSql($file) {
		$fp = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$query = '';
		foreach ($fp as $line) {
			if ($line != '' && strpos($line, '--') === false) {
				$query .= $line;
				if (substr($query, -1) == ';') {
					$this->nonQuery($query);
					$query = '';
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public function getSql() {
		return $this->sql;
	}

	/**
	 * @return string
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * @return string
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @return string
	 */
	public function getDatabase() {
		return $this->database;
	}
}