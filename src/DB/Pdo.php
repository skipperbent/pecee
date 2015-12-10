<?php
namespace Pecee\DB;

use Pecee\Collection\CollectionItem;
use Pecee\Debug;

class Pdo {

    protected static $instance;

    const SETTINGS_USERNAME = 'username';
    const SETTINGS_PASSWORD = 'password';
    const SETTINGS_CONNECTION_STRING = 'driver';

    protected $connection;
    protected $query;

    /**
     * Return new instance
     *
     * @return static
     */
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new static(env('DB_DRIVER', 'mysql') . ':host='.env('DB_HOST').';dbname='.env('DB_DATABASE').';charset=' . env('DB_CHARSET', 'utf8'),
                env('DB_USERNAME'), env('DB_PASSWORD'));
        }
        return self::$instance;
    }

    public function __construct($connectionString, $username, $password) {
        $this->connection = new \PDO($connectionString, $username, $password );
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Closing connection
     * http://php.net/manual/en/pdo.connections.php
     */
    public function __destruct() {
        $this->connection = null;
    }

    /**
     * Executes query
     *
     * @param string $query
     * @param array|null $parameters
     * @return \PDOStatement
     * @throws \PdoException
     */
    public function query($query, array $parameters = null) {

        $query = $this->connection->prepare($query);
        $inputParameters = null;

        if(is_array($parameters)) {
            $keyTest = array_keys($parameters)[0];

            if (!is_int($keyTest)) {
                foreach ($parameters as $key => $value) {
                    $query->bindParam($key, $value);
                }
            } else {
                $inputParameters = $parameters;
            }
        }

        $this->query = $query->queryString;
        Debug::getInstance()->add('START DB QUERY:' . $this->query);
        if($query->execute($inputParameters)) {
            Debug::getInstance()->add('END DB QUERY');
            return $query;
        }

        return null;
    }

    public function all($query, array $parameters = null) {
        $query = $this->query($query, $parameters);
        if($query) {
            $results = $query->fetchAll(\PDO::FETCH_ASSOC);
            $output = array();

            foreach($results as $result) {
                $output[] = new CollectionItem($result);
            }

            return $output;
        }

        return null;
    }

    public function single($query, array $parameters = null) {
        $result = $this->fetchAll($query. ' LIMIT 1', $parameters);
        if($result !== null) {
            return $result[0];
        }
        return null;
    }

    public function nonQuery($query, array $parameters = null) {
        $this->query($query, $parameters);
    }

    public function value($query, array $parameters = null) {
        $query = $this->query($query, $parameters);
        if($query) {
            return $query->fetchColumn(0);
        }
        return null;
    }

    public function insert($query, array $parameters = null) {
        $query = $this->query($query, $parameters);
        if($query) {
            return $this->connection->lastInsertId();
        }
        return null;
    }

    /**
     * Exucutes queries within a .sql file.
     *
     * @param string $file
     */
    public function executeSql($file) {
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
     * @return \PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getQuery(){
        return $this->query;
    }

}