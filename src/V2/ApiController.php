<?php

/**
 * Class ApiController.
 *
 * @package Rhorber\Inventory\API\V2
 * @author  Raphael Horber
 * @version 23.11.2019
 */
namespace Rhorber\Inventory\API\V2;

use Rhorber\Inventory\API\AbstractApiController;
use Rhorber\Inventory\API\Http;


/**
 * Serves incoming API calls. (Version 2)
 *
 * Request URI structure: `/api/v:version/:entity[/:id[/:action]]`
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 23.11.2019
 */
class ApiController extends AbstractApiController
{
    /**
     * Handles the request. Terminates the script execution.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
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
     * @version 23.11.2019
     */
    private function __construct()
    {
        parent::__construct();

        if ($this->entity === "articles") {
            $this->_handleArticlesRequest();
            return;
        }

        if ($this->entity === "categories") {
            $this->_handleCategoriesRequest();
            return;
        }

        Http::sendNotFound();
    }

    /**
     * Handles requests to "/api/v2/articles/...".
     *
     * Valid requests:
     * - "GET  .../articles"
     * - "GET  .../articles/:id"
     * - "PUT  .../articles/:id"
     * - "POST .../articles"
     * - "PUT  .../articles/:id/decrement"
     * - "PUT  .../articles/:id/increment"
     * - "PUT  .../articles/:id/move-down"
     * - "PUT  .../articles/:id/move-up"
     * - "PUT  .../articles/:id/reset"
     *
     * If the request is valid and not a preflight,
     * the database operation will be delegated to {@link ArticlesController}.
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    private function _handleArticlesRequest()
    {
        $knownActions = [null, "decrement", "increment", "move-down", "move-up", "reset"];
        if (in_array($this->action, $knownActions) === false) {
            Http::sendNotFound();
        }

        http_response_code(501);
        die();
    }

    /**
     * Handles requests to "/api/v2/categories/...".
     *
     * Valid requests:
     * - "GET  .../categories"
     * - "GET  .../categories/:id"
     * - "PUT  .../categories/:id"
     * - "POST .../categories"
     * - "GET  .../categories/:id/articles"
     * - "PUT  .../categories/:id/move-down"
     * - "PUT  .../categories/:id/move-up"
     *
     * If the request is valid the database operation will be delegated to {@link CategoriesController}.
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    private function _handleCategoriesRequest()
    {
        $controller = new CategoriesController();

        if ($this->method === "GET") {
            if ($this->action === null) {
                if ($this->entityId !== null) {
                    $controller->returnCategory($this->entityId);
                } else {
                    $controller->returnAllCategories();
                }
            } elseif ($this->action === "articles") {
                $controller->returnArticles($this->entityId);
            }
        } elseif ($this->method === "POST") {
            $controller->createCategory();
        } elseif ($this->method === "PUT") {
            if ($this->action === null) {
                $controller->updateCategory($this->entityId);
            } elseif ($this->action === "move-down") {
                $controller->moveDown($this->entityId);
            } elseif ($this->action === "move-up") {
                $controller->moveUp($this->entityId);
            }
        }

        Http::sendNotFound();
    }
}


// Útƒ-8 encoded
