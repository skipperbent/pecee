<?php
namespace Pecee\DB;

use Pecee\Collection\CollectionItem;

class Pdo
{

    protected static $instance;

    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @var \PDOStatement|null
     */
    protected $query;

    /**
     * Return new instance
     *
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static(env('DB_DRIVER', 'mysql') . ':host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE') . ';charset=' . env('DB_CHARSET', 'utf8'),
                env('DB_USERNAME'), env('DB_PASSWORD'));
        }

        return static::$instance;
    }

    protected function __construct($connectionString, $username, $password)
    {
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
    }

    /**
     * Executes query
     *
     * @param string $query
     * @param array $parameters
     * @return \PDOStatement|null
     * @throws \PdoException
     */
    public function query($query, array $parameters = [])
    {
        $statement = $this->connection->prepare($query);
        $inputParameters = null;

        if (\count($parameters) > 0) {
            $keyTest = array_keys($parameters)[0];

            if (\is_int($keyTest) === false) {
                foreach ($parameters as $key => $value) {
                    $statement->bindParam($key, $value);
                }
            } else {
                $inputParameters = $parameters;
            }
        }

        $this->query = $statement->queryString;
        debug('START DB QUERY:' . $this->query);
        if ($statement->execute($inputParameters)) {
            debug('END DB QUERY');
            return $statement;
        }

        return null;
    }

    /**
     * @param string $query
     * @param array|null $parameters
     * @return array
     * @throws \PDOException
     */
    public function all($query, array $parameters = [])
    {
        $query = $this->query($query, $parameters);
        if ($query !== null) {
            $results = $query->fetchAll(\PDO::FETCH_ASSOC);
            $output = [];

            foreach ($results as $result) {
                $output[] = new CollectionItem($result);
            }

            return $output;
        }

        return null;
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return string|null
     * @throws \PDOException
     */
    public function single($query, array $parameters = [])
    {
        $result = $this->all($query . ' LIMIT 1', $parameters);
        return ($result !== null) ? $result[0] : null;
    }

    /**
     * @param string $query
     * @param array $parameters
     * @throws \PDOException
     */
    public function nonQuery(string $query, array $parameters = [])
    {
        $this->query($query, $parameters);
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return string|null
     * @throws \PDOException
     */
    public function value($query, array $parameters = [])
    {
        $query = $this->query($query, $parameters);
        return ($query !== null) ? $query->fetchColumn() : null;
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return null|string
     * @throws \PDOException
     */
    public function insert($query, array $parameters = [])
    {
        return ($this->query($query, $parameters) !== null) ? $this->connection->lastInsertId() : null;
    }

    /**
     * Exucutes queries within a .sql file.
     *
     * @param string $file
     * @throws \PDOException
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