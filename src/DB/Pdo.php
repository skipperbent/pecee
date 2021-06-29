<?php

namespace Pecee\DB;

use PDOStatement;
use Pecee\Collection\CollectionItem;

class Pdo
{
    public const SETTINGS_USERNAME = 'username';
    public const SETTINGS_PASSWORD = 'password';
    public const SETTINGS_CONNECTION_STRING = 'driver';

    protected static ?self $instance = null;

    protected ?\PDO $connection = null;
    protected ?string $query = null;

    /**
     * Return new instance
     *
     * @return static
     */
    public static function getInstance(): self
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
    public function close(): void
    {
        $this->connection = null;
        static::$instance = null;
    }

    /**
     * Executes query
     *
     * @param string $query
     * @param array|null $parameters
     * @return PDOStatement|null
     * @throws \PdoException
     */
    public function query(string $query, ?array $parameters = null): ?PDOStatement
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

        debug('START DB QUERY:' . $this->query);

        if ($pdoStatement->execute($inputParameters) === true) {
            debug('END DB QUERY');

            return $pdoStatement;
        }

        return null;
    }

    public function all(string $query, ?array $parameters = null): ?array
    {
        $statement = $this->query($query, $parameters);
        if ($statement instanceof PDOStatement) {
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $output = [];

            foreach ($results as $result) {
                $output[] = new CollectionItem($result);
            }

            return $output;
        }

        return null;
    }

    public function single(string $query, ?array $parameters = null)
    {
        $result = $this->all($query . ' LIMIT 1', $parameters);

        return ($result !== null) ? $result[0] : null;
    }

    /**
     * @param string $query
     * @param array|null $parameters
     */
    public function nonQuery(string $query, ?array $parameters = null): void
    {
        $this->query($query, $parameters);
    }

    public function value(string $query, ?array $parameters = null)
    {
        $statement = $this->query($query, $parameters);

        return ($statement instanceof PDOStatement) ? $statement->fetchColumn() : null;
    }

    public function insert(string $query, array $parameters = null): ?string
    {
        return ($this->query($query, $parameters) instanceof PDOStatement) ? $this->connection->lastInsertId() : null;
    }

    /**
     * Exucutes queries within a .sql file.
     *
     * @param string $file
     */
    public function executeSql(string $file): void
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
    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    public function setConnection(\PDO $pdo): void
    {
        $this->connection = $pdo;
    }

    /**
     * @return string
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

}