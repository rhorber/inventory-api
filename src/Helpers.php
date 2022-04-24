<?php

/**
 * Class Helpers.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 24.04.2022
 */
namespace Rhorber\Inventory\API;


/**
 * Class containing helper functions.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 24.04.2022
 */
class Helpers
{
    /**
     * Full name of the .env file.
     *
     * @access public
     * @var    string
     */
    const ENV_FILE = __DIR__."/../.env";


    /**
     * If the file `.env` exists, lLoads it into `$_ENV`.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public static function loadEnvFile()
    {
        if (file_exists(self::ENV_FILE) === false) {
            return;
        }

        $config = parse_ini_file(self::ENV_FILE);
        if ($config === false) {
            return;
        }

        foreach ($config as $key => $value) {
            $_ENV[$key] = $value;
        }
    }

    /**
     * Validates if the expected environment variables exist and are not empty.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.04.2019
     */
    public static function validateEnvVariables()
    {
        foreach (['DATABASE_DSN', 'DATABASE_USERNAME', 'DATABASE_PASSWORD'] as $variableName) {
            if (empty($_ENV[$variableName])) {
                \error_log("Environment variable '".$variableName."' is not defined!");
                Http::sendServerError();
            }
        }

        if (empty($_ENV['ALLOWED_ORIGIN'])) {
            \error_log("Environment variable 'ALLOWED_ORIGIN' is not defined!");

            $query  = "
                INSERT INTO log (
                    type, content, client_ip, user_agent
                ) VALUES (
                    :type, :content, :clientIp, :userAgent
                )
            ";
            $values = [
                ':type'      => "error",
                ':content'   => "Environment variable 'ALLOWED_ORIGIN' is not defined!",
                ':clientIp'  => $_SERVER['REMOTE_ADDR'],
                ':userAgent' => $_SERVER['HTTP_USER_AGENT'],
            ];

            $database = new Database();
            $database->prepareAndExecute($query, $values, false);
            Http::sendServerError();
        }
    }

    /**
     * Decodes the request's payload and returns it.
     *
     * @return  array|null
     * @access  public
     * @author  Raphael Horber
     * @version 24.04.2022
     */
    public static function getPayload(): ?array
    {
        $payload = file_get_contents("php://input");
        $json    = json_decode($payload, true);

        return $json;
    }

    /**
     * Decodes the request's payload and returns it.
     *
     * @return     array|null
     * @access     public
     * @author     Raphael Horber
     * @version    24.04.2022
     * @deprecated Since 24.04.2022, use {@see getPayload()} instead.
     */
    public static function getSanitizedPayload(): ?array
    {
        return self::getPayload();
    }

    /**
     * Empty private constructor to prevent external object creation.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    private function __construct()
    {
    }
}


// Útƒ-8 encoded
