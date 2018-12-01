<?php

/**
 * Class Helpers.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.12.2018
 */
namespace Rhorber\Inventory\API;


/**
 * Class containing helper functions.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.12.2018
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
     * Decodes the request's payload, sanitizes it, and returns it.
     *
     * @return  array
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public static function getSanitizedPayload(): array
    {
        $payload = file_get_contents("php://input");
        $json    = json_decode($payload, true);

        if ($json !== false) {
            $json = self::_sanitizeArray($json);
        }

        return $json;
    }

    /**
     * Sanitizes the passed array (recursively).
     *
     * @param array $array Array to sanitize.
     *
     * @return  array
     * @access  private
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    private static function _sanitizeArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if ($value instanceof \Traversable) {
                $array[$key] = self::_sanitizeArray($value);
            } else {
                $array[$key] = filter_var($value, FILTER_SANITIZE_STRING);
            }
        }

        return $array;
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
