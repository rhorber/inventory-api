<?php

/**
 * Class Database.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.12.2018
 */
namespace Rhorber\Inventory\API;


/**
 * Database wrapper. Handles database connection and operations.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.12.2018
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
     * @version 01.12.2018
     */
    public function queryAndFetch(string $query): array
    {
        $statement = $this->_pdo->query($query);
        return $statement->fetchAll();
    }

    /**
     * Executes the passed query (as prepared statement) and returns its result.
     *
     * @param string $query      Query to execute.
     * @param array  $parameters Parameters to bind.
     *
     * @return  \PDOStatement Query's result as PDOStatement
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public function prepareAndExecute(string $query, array $parameters): \PDOStatement
    {
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
}


// Útƒ-8 encoded
