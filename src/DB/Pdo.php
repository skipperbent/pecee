<?php

namespace Pecee\DB;

use Pecee\Collection\CollectionItem;

class Pdo
{
    public const SETTINGS_USERNAME = 'username';
    public const SETTINGS_PASSWORD = 'password';
    public const SETTINGS_CONNECTION_STRING = 'driver';

    protected static $instance;

    protected $connection;
    protected $query;

    /**
     * Return new instance
     *
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static(
                env('DB_DRIVER', 'mysql') . ':host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE') . ';charset=' . env('DB_CHARSET', 'utf8'),
                env('DB_USERNAME'), env('DB_PASSWORD'));
        }

        return static::$instance;
    }

    protected function __construct($connectionString, $username, $password)
    {
        $this->query = null;
        $this->connection = new \PDO($connectionString, $username, $password);
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Closing connection
     * http://php.net/manual/en/pdo.connections.php
     */
    public function close()
    {
        $this->connection = null;
        static::$instance = null;
    }

    /**
     * Executes query
     *
     * @param string $query
     * @param array|null $parameters
     * @return \PDOStatement|null
     * @throws \PdoException
     */
    public function query($query, array $parameters = null)
    {
        $pdoStatement = $this->connection->prepare($query);
        $inputParameters = null;

        if ($parameters !== null && count($parameters) !== 0) {
            $keyTest = array_keys($parameters)[0];

            if (is_int($keyTest) === false) {
                foreach ($parameters as $key => $value) {
                    $pdoStatement->bindParam($key, $value);
                }
            } else {
                $inputParameters = $parameters;
            }
        }

        $this->query = $pdoStatement->queryString;
        debug('db', 'START DB QUERY: %s', $this->query);
        if ($pdoStatement->execute($inputParameters) === true) {
            debug('db', 'END DB QUERY');

            return $pdoStatement;
        }

        return null;
    }

    public function all($query, array $parameters = null)
    {
        $query = $this->query($query, $parameters);
        if ($query instanceof \PDOStatement) {
            $results = $query->fetchAll(\PDO::FETCH_ASSOC);
            $output = [];

            foreach ($results as $result) {
                $output[] = new CollectionItem($result);
            }

            return $output;
        }

        return null;
    }

    public function single($query, array $parameters = null)
    {
        $result = $this->all($query . ' LIMIT 1', $parameters);

        return ($result !== null) ? $result[0] : null;
    }

    /**
     * @param $query
     * @param array|null $parameters
     */
    public function nonQuery($query, array $parameters = null)
    {
        $this->query($query, $parameters);
    }

    public function value($query, array $parameters = null)
    {
        $query = $this->query($query, $parameters);

        return ($query instanceof \PDOStatement) ? $query->fetchColumn() : null;
    }

    public function insert($query, array $parameters = null)
    {
        $query = $this->query($query, $parameters);

        return ($query instanceof \PDOStatement) ? $this->connection->lastInsertId() : null;
    }

    /**
     * Exucutes queries within a .sql file.
     *
     * @param string $file
     */
    public function executeSql($file)
    {
        $fp = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $query = '';
        foreach ($fp as $line) {
            if ($line !== '' && strpos($line, '--') === false) {
                $query .= $line;
                if (substr($query, -1) === ';') {
                    $this->nonQuery($query);
                    $query = '';
                }
            }
        }
    }

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

}