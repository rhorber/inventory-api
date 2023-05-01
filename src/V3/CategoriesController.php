<?php

/**
 * Class CategoriesController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API\V3;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Helpers;
use Rhorber\Inventory\API\Http;
use Rhorber\Inventory\API\V3\Entities\Category;


/**
 * Class for modifying or adding a category. All methods terminate execution.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 01.05.2023
 */
class CategoriesController
{
    /**
     * Database connection.
     *
     * @access private
     * @var    Database
     */
    private $_database;

    /**
     * Collection `categories` of the database.
     *
     * @access private
     * @var    \MongoDB\Collection
     */
    private $_categories;


    /**
     * Initializes a new instance of the `CategoriesController` class.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function __construct()
    {
        $this->_database   = new Database();
        $this->_categories = $this->_database->categories;
    }

    /**
     * Returns all categories as JSON response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function returnAllCategories()
    {
        $cursor = $this->_categories->find([]);

        $entities = Category::mapToEntities($cursor);
        $response = ['categories' => $entities];

        Http::sendJsonResponse($response);
    }

    /**
     * Returns the category as JSON response.
     *
     * @param integer $categoryId ID of the category to return.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function returnCategory(int $categoryId)
    {
        /** @var \MongoDB\Model\BSONDocument $category */
        $category = $this->_categories->findOne(
            ['_id' => $categoryId]
        );

        if ($category === null) {
            Http::sendNotFound();
        }

        $response = Category::mapToEntity($category);

        Http::sendJsonResponse($response);
    }

    /**
     * Returns all articles from the category as JSON response.
     *
     * This endpoint is not used by the current client app.
     *
     * @param integer $categoryId ID of the category to return the articles from.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function returnArticles(int $categoryId)
    {
        $filter   = [
            'category' => $categoryId,
        ];
        $entities = ArticlesController::getArticleEntities(
            $this->_database,
            $filter
        );

        $response = ['articles' => $entities];

        Http::sendJsonResponse($response);
    }

    /**
     * Adds a new category from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function createCategory()
    {
        $payload = Helpers::getPayload();

        $categoryId = $this->_database->getNextValue(
            $this->_categories,
            "_id"
        );
        $position   = $this->_database->getNextValue(
            $this->_categories,
            "position"
        );
        $timestamp  = $payload['timestamp'] ?? $this->_database->nowTimestamp;

        $document = [
            '_id'       => $categoryId,
            'name'      => $payload['name'],
            'position'  => $position,
            'timestamp' => $timestamp,
        ];
        $this->_categories->insertOne($document);

        Http::sendNoContent();
    }

    /**
     * Updates the category from payload.
     *
     * @param integer $categoryId ID of the category to update.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function updateCategory(int $categoryId)
    {
        $payload = Helpers::getPayload();

        $timestamp = $this->_database->getNewTimestamp(
            $this->_categories,
            $categoryId,
            $payload['timestamp']
        );

        // Update only if the category was not updated since this cached update.
        if ($timestamp === false) {
            Http::sendNoContent();
        }

        $updateFields = [
            'name'      => $payload['name'],
            'timestamp' => $timestamp,
        ];
        $this->_categories->updateOne(
            ['_id' => $categoryId],
            ['$set' => $updateFields]
        );

        Http::sendNoContent();
    }

    /**
     * Moves the category one position down.
     *
     * @param integer $categoryId ID of the category to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function moveDown(int $categoryId)
    {
        $this->_moveCategory($categoryId, false);
    }

    /**
     * Moves the category one position up.
     *
     * @param integer $categoryId ID of the category to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function moveUp(int $categoryId)
    {
        $this->_moveCategory($categoryId, true);
    }

    /**
     * Moves the category one position up or down.
     *
     * @param integer $categoryId ID of the category to move.
     * @param boolean $moveUp     Whether to move the document up (decrease position).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    private function _moveCategory(int $categoryId, bool $moveUp)
    {
        // Get position of the category to move.
        $category = $this->_categories->findOne(
            ['_id' => $categoryId],
            ['projection' => ['position' => 1]]
        );

        // Move categories.
        $categories = $this->_database->moveDocument(
            $this->_categories,
            $categoryId,
            $category['position'],
            $moveUp,
            []
        );

        // Proceed only if any categories were moved.
        if ($categories === false) {
            Http::sendBadRequest();
        }

        // Return the updated categories.
        $entities = Category::mapToEntities($categories);
        $response = ['categories' => $entities];

        Http::sendJsonResponse($response);
    }
}


// Útƒ-8 encoded
