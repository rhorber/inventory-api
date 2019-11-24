<?php

/**
 * Class RequestDispatcher.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 23.11.2019
 */
namespace Rhorber\Inventory\API;


/**
 * Dispatches incoming API calls by the version to the API controllers.
 *
 * Request URI structure: `/api/v:version/:entity[/:id[/:action]]`
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 23.11.2019
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
     * @version 23.11.2019
     */
    private function __construct()
    {
        $prefix = mb_substr($_SERVER['REQUEST_URI'], 0, 8);

        if ($prefix === "/api/v1/") {
            V1\ApiController::handleRequest();
        } else if ($prefix === "/api/v2/") {
            V2\ApiController::handleRequest();
        }

        Http::sendNotFound();
    }
}


// Útƒ-8 encoded
