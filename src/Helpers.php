<?php

/**
 * Class Helpers.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API;


/**
 * Class containing helper functions.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.05.2023
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
     * @version 01.05.2023
     */
    public static function validateEnvVariables()
    {
        foreach (['DATABASE_URI', 'DATABASE_NAME'] as $variableName) {
            if (empty($_ENV[$variableName])) {
                \error_log("Environment variable '".$variableName."' is not defined!");
                Http::sendServerError();
            }
        }

        if (empty($_ENV['ALLOWED_ORIGIN'])) {
            $content = "Environment variable 'ALLOWED_ORIGIN' is not defined!";

            \error_log($content);
            Database::newLogEntry("error", $content);

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
