<?php
namespace Pecee\DB;

use Pecee\Collection;
use Pecee\Registry;

class Pdo {

    protected static $instance;

    const SETTINGS_USERNAME = 'username';
    const SETTINGS_PASSWORD = 'password';
    const SETTINGS_DRIVER = 'driver';

    protected $connection;

    /**
     * Return new instance
     *
     * @return static
     */
    public static function getInstance() {
        if(self::$instance === null) {
            $registry = Registry::getInstance();
            self::$instance = new static($registry->get(self::SETTINGS_DRIVER), $registry->get(self::SETTINGS_USERNAME), $registry->get(self::SETTINGS_PASSWORD));
        }
        return self::$instance;
    }

    public function __construct($driver, $username, $password) {
        try {
            $this->connection = new \PDO($driver, $username, $password );
        }catch(\PDOException $e) {
            throw new PdoException($e->getMessage(), $e->getCode());
        }
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
     * @throws PdoException
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

        try {
            if($query->execute($inputParameters)) {
                return $query;
            }
        }catch(\PDOException $e) {
            throw new PdoException($e->getMessage(), $e->getCode(), $query->queryString);
        }
    }

    public function fetchAll($query, array $parameters = null) {
        $query = $this->query($query, $parameters);
        if($query) {
            $results = $query->fetchAll(\PDO::FETCH_ASSOC);
            $output = array();

            foreach($results as $result) {
                $output[] = new Collection($result);
            }

            return $output;
        }

        return null;
    }

    public function fetchSingle($query, array $parameters = null) {
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
     * @return \PDO
     */
    public function getConnection() {
        return $this->connection;
    }

}