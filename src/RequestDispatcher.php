<?php

/**
 * Class RequestDispatcher.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 06.05.2023
 */
namespace Rhorber\Inventory\API;


/**
 * Dispatches incoming API calls by the version to the API controllers.
 *
 * Request URI structure: `/api/v:version/:entity[/:id[/:action]]`
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 06.05.2023
 */
class RequestDispatcher
{
    /**
     * Dispatches the request. Terminates the script execution.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    public static function dispatchRequest()
    {
        new RequestDispatcher();
    }

    /**
     * Constructor: Parses the request for the version and delegates its fulfilment to the specific controller.
     *
     * @access  private
     * @author  Raphael Horber
     * @version 06.05.2023
     */
    private function __construct()
    {
        $requestUri = $_SERVER['REQUEST_URI'];

        if (in_array($requestUri, ['/', '/index.php'], true)) {
            Http::sendJsonResponse(["Hello, World!"]);
        } elseif ($requestUri === "/robots933456.txt") {
            // Dummy request from Azure App Service.
            // See: https://learn.microsoft.com/en-gb/azure/app-service/configure-language-php?pivots=platform-linux#robots933456-in-logs
            Http::sendNoContent();
        }

        $prefix = mb_substr($_SERVER['REQUEST_URI'], 0, 8);

        if ($prefix === "/api/v3/") {
            V3\ApiController::handleRequest();
        }

        Http::sendNotFound();
    }
}


// Útƒ-8 encoded
