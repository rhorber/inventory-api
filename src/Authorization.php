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
        if (strtolower(substr($auth, 0, 7)) !== 'bearer ') {
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

        $query = "SELECT * FROM tokens WHERE token = :token AND active = 1";
        $values = [':token' => $token];

        $database = new Database();
        $statement = $database->prepareAndExecute($query, $values);

        if ($statement->rowCount() !== 1) {
            Http::sendUnauthorized();
        }
    }
}


// Útƒ-8 encoded
