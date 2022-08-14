<?php

/**
 * Class Authorization.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 30.03.2019
 */
namespace Rhorber\Inventory\API;


/**
 * Verifies the Authorization header of API calls.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 30.03.2019
 */
class Authorization
{
    /**
     * Name of the client which belongs to the request's authorization token.
     *
     * @access private
     * @var    string
     */
    private static $_clientName;


    /**
     * Checks for an authorization, and if so verifies it.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 30.03.2019
     */
    public static function verifyAuth()
    {
        new Authorization();
    }

    /**
     * Returns the name of the client which belongs to the request's authorization token.
     *
     * @return string Name of the authorized client.
     */
    public static function getClientName()
    {
        return self::$_clientName;
    }

    /**
     * Constructor: Parses the Authorization header and verifies the token.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 30.03.2019
     */
    private function __construct()
    {
        if (empty($_SERVER['HTTP_AUTHORIZATION']) === true) {
            Http::sendUnauthorized();
        }

        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (mb_strtolower(mb_substr($auth, 0, 7)) !== 'bearer ') {
            Http::sendUnauthorized();
        }

        $this->_verifyToken($auth);
    }

    /**
     * Checks the token against the database.
     *
     * @param string $authorization Full content of the Authorization header (including 'Bearer ')
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 30.03.2019
     */
    private function _verifyToken(string $authorization)
    {
        $token = substr($authorization, 7);

        $query  = "
            SELECT name
            FROM tokens
            WHERE token = :token AND active = 1
        ";
        $values = [':token' => $token];

        $database  = new Database();
        $statement = $database->prepareAndExecute($query, $values);

        if ($statement->rowCount() !== 1) {
            Http::sendUnauthorized();
        }
        $row = $statement->fetch();

        self::$_clientName = $row['name'];
    }
}


// Útƒ-8 encoded
