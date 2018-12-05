<?php

/**
 * Class ApiController.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 02.12.2018
 */
namespace Rhorber\Inventory\API;


/**
 * Serves incoming API calls. (Version 1)
 *
 * Request URI structure: `/api/v:version/:entity[/:id[/:action]]`
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 02.12.2018
 */
class ApiController
{
    /**
     * Full request uri (`$_SERVER['REQUEST_URI']`).
     *
     * @access private
     * @var    string
     */
    private $_uri;

    /**
     * Request method (`$_SERVER['REQUEST_METHOD']`).
     *
     * @access private
     * @var    string
     */
    private $_method;

    /**
     * Request's entity segment.
     *
     * @access private
     * @var    string
     */
    private $_entity;

    /**
     * Request's id segment.
     *
     * @access private
     * @var    string
     */
    private $_entityId;

    /**
     * Request's action segment.
     *
     * @access private
     * @var    string
     */
    private $_action;


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
     * @access  public
     * @author  Raphael Horber
     * @version 02.12.2018
     */
    private function __construct()
    {
        $this->_uri    = $_SERVER['REQUEST_URI'];
        $this->_method = $_SERVER['REQUEST_METHOD'];

        error_log("Request: ".$this->_method." ".$this->_uri);

        $this->_validatePrefix();
        $this->_parseUri();

        if ($this->_entity === "inventory") {
            $this->_handleInventoryRequest();
            return;
        }

        if ($this->_entity === "item") {
            $this->_handleItemRequest();
            return;
        }

        Http::sendNotFound();
    }

    /**
     * Validates the URI prefix (must be "/api/v1/").
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    private function _validatePrefix()
    {
        if (mb_substr($this->_uri, 0, 8) !== "/api/v1/") {
            Http::sendNotFound();
        }
    }

    /**
     * Parses the URI (sets the private segment properties).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    private function _parseUri()
    {
        $uri = mb_substr($this->_uri, 8);

        list($this->_entity, $this->_entityId, $this->_action) = explode("/", $uri);

        if ($this->_entityId !== null && intval($this->_entityId) === 0) {
            Http::sendNotFound();
        }
    }

    /**
     * Handles requests to ".../inventory". Including preflight requests.
     *
     * Only valid request is "GET .../inventory", which returns all items.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    private function _handleInventoryRequest()
    {
        if ($this->_uri !== "/api/v1/inventory") {
            Http::sendNotFound();
        }

        if ($this->_method === "OPTIONS") {
            header("Access-Control-Allow-Headers: Content-Type");
            Http::sendNoContent();
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
     * @access  public
     * @author  Raphael Horber
     * @version 02.12.2018
     */
    private function _handleItemRequest()
    {
        $knownActions = [null, "increment", "decrement", "reset-stock", "move-down", "move-up"];
        if (in_array($this->_action, $knownActions) === false) {
            Http::sendNotFound();
        }

        if ($this->_method === "OPTIONS") {
            header("Access-Control-Allow-Headers: Content-Type");
            header("Access-Control-Allow-Methods: PUT");
            Http::sendNoContent();
        }

        $controller = new ItemController($this->_entityId);

        if ($this->_action === "decrement") {
            $controller->decrementStock();
        } elseif ($this->_action === "increment") {
            $controller->incrementStock();
        } elseif ($this->_action === "move-down") {
            $controller->moveDown();
        } elseif ($this->_action === "move-up") {
            $controller->moveUp();
        } elseif ($this->_action === "reset-stock") {
            $controller->resetStock();
        }

        if ($this->_method === "GET") {
            $controller->returnItem();
        } elseif ($this->_method === "PUT") {
            $controller->updateItem();
        } elseif ($this->_method === "POST") {
            $controller->addItem();
        }
    }
}


// Útƒ-8 encoded