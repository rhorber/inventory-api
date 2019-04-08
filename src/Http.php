<?php

/**
 * Class Http.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 04.04.2019
 */
namespace Rhorber\Inventory\API;


/**
 * Class for sending HTTP responses. All methods terminate execution.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 04.04.2019
 */
class Http
{
    /**
     * Sends a 200 response with the response as encoded JSON.
     *
     * @param array $response Response to send as encoded JSON.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2019
     */
    public static function sendJsonResponse(array $response)
    {
        self::_setCorsHeaders();
        header("Content-Type: application/json");

        http_response_code(200);

        $json = json_encode($response);
        die($json);
    }

    /**
     * Sends an empty 204 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2019
     */
    public static function sendNoContent()
    {
        self::_setCorsHeaders();

        http_response_code(204);
        die();
    }

    /**
     * Sends an unauthorized 401 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2019
     */
    public static function sendUnauthorized()
    {
        self::_setCorsHeaders();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
        }else{
            http_response_code(401);
        }
        die();
    }

    /**
     * Sends an empty 404 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public static function sendNotFound()
    {
        http_response_code(404);
        die();
    }

    /**
     * Sends an empty 500 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public static function sendServerError()
    {
        http_response_code(500);
        die();
    }

    /**
     * Sets the "Access-Control-Allow-*" headers.
     *
     * - the "Access-Control-Allow-Origin" header with `$_ENV['ALLOWED_ORIGIN']`
     * - the "Access-Control-Allow-Headers" header with "Authorization"
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2019
     */
    private static function _setCorsHeaders()
    {
        // TODO: Verify 'Access-Control-Request-*' headers; send all 'Access-Control-Allow-*' headers conditionally
        // TODO: Set "Access-Control-Max-Age" header.
        if (empty($_ENV['ALLOWED_ORIGIN']) === false) {
            header("Access-Control-Allow-Origin: ".$_ENV['ALLOWED_ORIGIN']);
            header("Vary: Origin");
        }
        header("Access-Control-Allow-Headers: Authorization, Content-Type");
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
