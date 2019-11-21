<?php

/**
 * Class Database.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 21.11.2019
 */
namespace Rhorber\Inventory\API;


/**
 * Database wrapper. Handles database connection and operations.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 21.11.2019
 */
class Database
{
    /**
     * PDO object (actual database connection).
     *
     * @access private
     * @var    \PDO
     */
    private $_pdo;


    /**
     * Constructor: Validates the env variables and connects to the database.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public function __construct()
    {
        $this->_validateEnvVars();

        $dsn      = $_ENV['DATABASE_DSN'];
        $username = $_ENV['DATABASE_USERNAME'];
        $password = $_ENV['DATABASE_PASSWORD'];
        $options  = [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        $this->_pdo = new \PDO($dsn, $username, $password, $options);
    }

    /**
     * Executes the SELECT statement and fetches the rows.
     *
     * @param string $query SELECT statement to execute.
     *
     * @return  array[] The result rows as associative arrays.
     * @access  public
     * @author  Raphael Horber
     * @version 09.12.2018
     */
    public function queryAndFetch(string $query): array
    {
        $this->_logQuery($query);
        $statement = $this->_pdo->query($query);
        return $statement->fetchAll();
    }

    /**
     * Executes the passed query (as prepared statement) and returns its result.
     *
     * @param string  $query      Query to execute.
     * @param array   $parameters Parameters to bind.
     * @param boolean $logQuery   Whether to log the query or not (default: true).
     *
     * @return  \PDOStatement Query's result as PDOStatement
     * @access  public
     * @author  Raphael Horber
     * @version 09.12.2018
     */
    public function prepareAndExecute(string $query, array $parameters, bool $logQuery = true): \PDOStatement
    {
        if ($logQuery === true) {
            $this->_logQuery($query, $parameters);
        }

        $statement = $this->_pdo->prepare($query);
        foreach ($parameters as $parameter => $value) {
            $statement->bindValue($parameter, $value);
        }

        $statement->execute();
        return $statement;
    }

    /**
     * Validates if the necessary env variables exist, if not sends a 500 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    private function _validateEnvVars()
    {
        if (isset($_ENV['DATABASE_DSN']) === false
            || isset($_ENV['DATABASE_USERNAME']) === false
            || isset($_ENV['DATABASE_PASSWORD']) === false
        ) {
            Http::sendServerError();
        }
    }

    /**
     * Logs a query into the database.
     *
     * @param string $logQuery  Query to log.
     * @param array  $logValues Values to log.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    private function _logQuery(string $logQuery, array $logValues = [])
    {
        if (empty($_ENV['DEBUG']) || $_ENV['DEBUG'] !== "true") {
            return;
        }

        $content = $logQuery;
        if (count($logValues) > 0) {
            $content .= " | ".print_r($logValues, true);
        }

        $insertQuery  = "
            INSERT INTO log (
                type, content, client_name, client_ip, user_agent
            ) VALUES (
                :type, :content, :clientName, :clientIp, :userAgent
            )
        ";
        $insertValues = [
            ':type'       => 'query',
            ':content'    => str_replace(["\n", "\r"], " ", $content),
            ':clientName' => Authorization::getClientName(),
            ':clientIp'   => $_SERVER['REMOTE_ADDR'],
            ':userAgent'  => $_SERVER['HTTP_USER_AGENT'],
        ];

        $this->prepareAndExecute($insertQuery, $insertValues, false);
    }
}


// Útƒ-8 encoded
