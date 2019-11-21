<?php

/**
 * Class ApiController.
 *
 * @package Rhorber\Inventory\API\V1
 * @author  Raphael Horber
 * @version 21.11.2019
 */
namespace Rhorber\Inventory\API\V1;

use Rhorber\Inventory\API\AbstractApiController;
use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Http;


/**
 * Serves incoming API calls, Version 1.
 *
 * Request URI structure: `/api/v:version/:entity[/:id[/:action]]`
 *
 * @package Rhorber\Inventory\API\V1
 * @author  Raphael Horber
 * @version 21.11.2019
 */
class ApiController extends AbstractApiController
{
    /**
     * Handles the request. Terminates the script execution.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public static function handleRequest()
    {
        new ApiController();
    }

    /**
     * Constructor: Parses the request and delegates its fulfilment to the specific class/method.
     *
     * @access  private
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    private function __construct()
    {
        parent::__construct();

        if ($this->entity === "inventory") {
            $this->_handleInventoryRequest();
            return;
        }

        if ($this->entity === "item") {
            $this->_handleItemRequest();
            return;
        }

        Http::sendNotFound();
    }

    /**
     * Handles requests to ".../inventory". Including preflight requests.
     *
     * Only valid request is "GET .../inventory", which returns all items.
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    private function _handleInventoryRequest()
    {
        if ($this->uri !== "/api/v1/inventory") {
            Http::sendNotFound();
        }

        $database = new Database();
        $items    = $database->queryAndFetch("SELECT * FROM items");

        $response = ['items' => $items];
        Http::sendJsonResponse($response);
    }

    /**
     * Handles requests to ".../item". Including preflight requests.
     *
     * Valid requests:
     * - "GET .../item/:id"
     * - "GET .../item/:id/decrement"
     * - "GET .../item/:id/increment"
     * - "GET .../item/:id/reset-stock"
     * - "GET .../item/:id/move-down"
     * - "GET .../item/:id/move-up"
     * - "PUT .../item/:id"
     * - "POST .../item"
     *
     * If the request is valid and not a preflight,
     * the database operation will be delegated to {@link ItemController}.
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    private function _handleItemRequest()
    {
        $knownActions = [null, "increment", "decrement", "reset-stock", "move-down", "move-up"];
        if (in_array($this->action, $knownActions) === false) {
            Http::sendNotFound();
        }

        $controller = new ItemController($this->entityId);

        if ($this->action === "decrement") {
            $controller->decrementStock();
        } elseif ($this->action === "increment") {
            $controller->incrementStock();
        } elseif ($this->action === "move-down") {
            $controller->moveDown();
        } elseif ($this->action === "move-up") {
            $controller->moveUp();
        } elseif ($this->action === "reset-stock") {
            $controller->resetStock();
        }

        if ($this->method === "GET") {
            $controller->returnItem();
        } elseif ($this->method === "PUT") {
            $controller->updateItem();
        } elseif ($this->method === "POST") {
            $controller->addItem();
        }
    }
}


// Útƒ-8 encoded
