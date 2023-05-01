<?php

/**
 * Class Http.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API;


/**
 * Class for sending HTTP responses. All methods terminate execution.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.05.2023
 */
class Http
{
    /**
     * Methods allowed to request with `Access-Control-Request-Method` header (uppercase).
     *
     * @access private
     * @var    string[]
     */
    private static $allowedMethods = ['GET', 'PUT', 'POST', 'DELETE'];

    /**
     * Headers allowed to request with `Access-Control-Request-Headers` header (lowercase).
     *
     * @access private
     * @var    string[]
     */
    private static $allowedHeaders = ['authorization', 'content-type'];


    /**
     * Handles the server side of CORS (verifies and sets headers, and handles and verifies preflight requests).
     *
     * - Verifies the Origin. If it is invalid, aborts with an error response
     * - Checks for a preflight request.
     * - If it is a preflight request, verifies it. If it is invalid, aborts with an error response.
     * - Sets the `Access-Control-Allow-Origin` header.
     *
     * @param string $method Request method (`$_SERVER['REQUEST_METHOD']`).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    public static function handleCors(string $method)
    {
        self::_verifyOrigin();

        if ($method === 'OPTIONS' && empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) === false) {
            self::_handlePreflightRequest();
            return;
        }

        header('Access-Control-Allow-Origin: '.$_ENV['ALLOWED_ORIGIN']);
        header("Vary: Origin");
    }

    /**
     * Sends a 200 response with the response as encoded JSON.
     *
     * @param array|object $response Response to send as encoded JSON.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2022
     */
    public static function sendJsonResponse($response)
    {
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
     * @version 20.04.2019
     */
    public static function sendNoContent()
    {
        http_response_code(204);
        die();
    }

    /**
     * Sends a bad request 400 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    public static function sendBadRequest()
    {
        http_response_code(400);

        self::_logRequest();
        die();
    }

    /**
     * Sends an unauthorized 401 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    public static function sendUnauthorized()
    {
        http_response_code(401);

        self::_logRequest();
        die();
    }

    /**
     * Sends a forbidden 403 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    public static function sendForbidden()
    {
        http_response_code(403);

        self::_logRequest();
        die();
    }

    /**
     * Sends an empty 404 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    public static function sendNotFound()
    {
        http_response_code(404);

        self::_logRequest();
        die();
    }

    /**
     * Sends a method not allowed 405 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    public static function sendMethodNotAllowed()
    {
        http_response_code(405);

        self::_logRequest();
        die();
    }

    /**
     * Sends an empty 500 response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    public static function sendServerError()
    {
        http_response_code(500);

        self::_logRequest();
        die();
    }

    /**
     * Verifies the `Origin` header.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 20.04.2019
     */
    private static function _verifyOrigin()
    {
        if (empty($_SERVER['HTTP_ORIGIN'])) {
            Http::sendBadRequest();
        } elseif ($_SERVER['HTTP_ORIGIN'] !== $_ENV['ALLOWED_ORIGIN']) {
            Http::sendForbidden();
        }
    }

    /**
     * Handles a preflight (`OPTIONS`) request.
     *
     * It verifies the `Access-Control-Request-*` headers (if something is invalid, aborts with an error response)
     * and sets the `Access-Control-Allow-*` headers.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 20.04.2019
     */
    private static function _handlePreflightRequest()
    {
        if (in_array($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'], self::$allowedMethods) === false) {
            Http::sendMethodNotAllowed();
        }

        if (empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) === false) {
            $requestedHeaders = mb_strtolower($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);

            foreach (explode(',', $requestedHeaders) as $requestedHeader) {
                if (in_array($requestedHeader, self::$allowedHeaders) === false) {
                    Http::sendForbidden();
                }
            }
        }

        // TODO: Verify Methods and Headers per resource and set response headers accordingly.
        header('Access-Control-Allow-Methods: '.implode(', ', self::$allowedMethods));
        header('Access-Control-Allow-Headers: '.implode(', ', self::$allowedHeaders));
        header('Access-Control-Allow-Origin: '.$_ENV['ALLOWED_ORIGIN']);
        header("Vary: Origin");
        Http::sendNoContent();
    }

    /**
     * Logs an invalid/unauthorized request into the database.
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    private static function _logRequest()
    {
        $method  = mb_strtoupper($_SERVER['REQUEST_METHOD']);
        $content = sprintf('"%s %s" %d', $method, $_SERVER['REQUEST_URI'], http_response_code());

        Database::newLogEntry("request", $content);
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
