<?php

/**
 * Class ApiController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 05.08.2020
 */
namespace Rhorber\Inventory\API\V3;

use Rhorber\Inventory\API\AbstractApiController;
use Rhorber\Inventory\API\Http;


/**
 * Serves incoming API calls. (Version 3)
 *
 * Request URI structure: `/api/v:version/:entity[/:id[/:action]]`
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 05.08.2020
 */
class ApiController extends AbstractApiController
{
    /**
     * Handles the request. Terminates the script execution.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.08.2020
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
     * @version 05.08.2020
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

        if ($this->entity === "lots") {
            $this->_handleLotsRequest();
            return;
        }

        Http::sendNotFound();
    }

    /**
     * Handles requests to "/api/v3/articles/...".
     *
     * Valid requests:
     * - "GET  .../articles"
     * - "GET  .../articles/:id"
     * - "PUT  .../articles/:id"
     * - "POST .../articles"
     * - "PUT  .../articles/:id/move-down"
     * - "PUT  .../articles/:id/move-up"
     * - "PUT  .../articles/:id/reset"
     *
     * If the request is valid the database operation will be delegated to {@link ArticlesController}.
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    private function _handleArticlesRequest()
    {
        $controller = new ArticlesController();

        if ($this->method === "GET" && $this->action === null) {
            if ($this->entityId !== null) {
                $controller->returnArticle($this->entityId);
            } else {
                $controller->returnAllArticles();
            }
        } elseif ($this->method === "POST") {
            $controller->createArticle();
        } elseif ($this->method === "PUT") {
            if ($this->action === null) {
                $controller->updateArticle($this->entityId);
            } elseif ($this->action === "move-down") {
                $controller->moveDown($this->entityId);
            } elseif ($this->action === "move-up") {
                $controller->moveUp($this->entityId);
            } elseif ($this->action === "reset") {
                $controller->resetArticle($this->entityId);
            }
        }

        Http::sendNotFound();
    }

    /**
     * Handles requests to "/api/v3/categories/...".
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
     * @version 05.08.2020
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

    /**
     * Handles requests to "/api/v3/lots/...".
     *
     * Valid requests:
     * - "PUT  .../lots/:id"
     * - "POST .../lots"
     * - "PUT  .../lots/:id/decrement"
     * - "PUT  .../lots/:id/increment"
     * - "PUT  .../lots/:id/move-down"
     * - "PUT  .../lots/:id/move-up"
     *
     * If the request is valid the database operation will be delegated to {@link LotsController}.
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    private function _handleLotsRequest()
    {
        $controller = new LotsController();

        if ($this->method === "POST") {
            $controller->createLot();
        } elseif ($this->method === "PUT") {
            if ($this->action === null) {
                $controller->updateLot($this->entityId);
            } elseif ($this->action === "decrement") {
                $controller->decrementStock($this->entityId);
            } elseif ($this->action === "increment") {
                $controller->incrementStock($this->entityId);
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
