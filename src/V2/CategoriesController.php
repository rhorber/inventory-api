<?php

/**
 * Class CategoriesController.
 *
 * @package Rhorber\Inventory\API\V2
 * @author  Raphael Horber
 * @version 23.11.2019
 */
namespace Rhorber\Inventory\API\V2;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Helpers;
use Rhorber\Inventory\API\Http;


/**
 * Class for modifying or adding a category. All methods terminate execution.
 *
 * @package Rhorber\Inventory\API\V2
 * @author  Raphael Horber
 * @version 23.11.2019
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
     * Constructor: Connects to the database.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function __construct()
    {
        $this->_database = new Database();
    }

    /**
     * Returns all categories as JSON response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function returnAllCategories()
    {
        $query      = "SELECT * FROM categories";
        $categories = $this->_database->queryAndFetch($query);

        $response = ['categories' => $categories];
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
     * @version 23.11.2019
     */
    public function returnCategory(int $categoryId)
    {
        $query  = "SELECT * FROM categories WHERE id = :id";
        $params = [':id' => $categoryId];

        $statement = $this->_database->prepareAndExecute($query, $params);
        $category  = $statement->fetch();

        Http::sendJsonResponse($category);
    }

    /**
     * Returns all articles from the category as JSON response.
     *
     * @param integer $categoryId ID of the category to return the articles from.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function returnArticles(int $categoryId)
    {
        $query  = "SELECT * FROM articles WHERE category = :id";
        $params = [':id' => $categoryId];

        $statement = $this->_database->prepareAndExecute($query, $params);
        $articles  = $statement->fetchAll();

        $response = ['articles' => $articles];
        Http::sendJsonResponse($response);
    }

    /**
     * Adds a new category from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function createCategory()
    {
        $maxQuery  = "
            SELECT COALESCE(MAX(position), 0) + 1 AS new_position
            FROM categories
        ";
        $maxResult = $this->_database->queryAndFetch($maxQuery);
        $position  = $maxResult[0]['new_position'];

        $payload = Helpers::getSanitizedPayload();

        $insertQuery = "
            INSERT INTO categories (
                name, position
            ) VALUES (
                :name, :position
            )
        ";
        $params      = [
            ':name'     => $payload['name'],
            ':position' => $position,
        ];

        $this->_database->prepareAndExecute($insertQuery, $params);
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
     * @version 23.11.2019
     */
    public function updateCategory(int $categoryId)
    {
        $payload = Helpers::getSanitizedPayload();

        $query  = "
            UPDATE categories SET
                name = :name
            WHERE id = :id
        ";
        $params = [
            ':id'   => $categoryId,
            ':name' => $payload['name'],
        ];

        $this->_database->prepareAndExecute($query, $params);
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
     * @version 23.11.2019
     */
    public function moveDown(int $categoryId)
    {
        $this->_moveCategory($categoryId, "+", "-");
    }

    /**
     * Moves the category one position up.
     *
     * @param integer $categoryId ID of the category to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function moveUp(int $categoryId)
    {
        $this->_moveCategory($categoryId, "-", "+");
    }

    /**
     * Moves the category one position up or down.
     *
     * @param integer $categoryId     ID of the category to move.
     * @param string  $thisDirection  Direction of this category ("+" or "-" for down or up respectively).
     * @param string  $otherDirection Direction of the other category ("-" or "+" for down or up respectively).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    private function _moveCategory(int $categoryId, string $thisDirection, string $otherDirection)
    {
        $query  = "SELECT position FROM categories WHERE id = :id";
        $params = [':id' => $categoryId];

        $statement    = $this->_database->prepareAndExecute($query, $params);
        $thisCategory = $statement->fetch();
        $position     = $thisCategory['position'];

        $moveOther   = "
            UPDATE categories SET
                position = position ".$otherDirection." 1
            WHERE position = :position ".$thisDirection." 1
        ";
        $paramsOther = [':position' => $position];
        $this->_database->prepareAndExecute($moveOther, $paramsOther);

        $moveThis = "
            UPDATE categories SET
                position = position ".$thisDirection." 1
            WHERE id = :id
        ";
        $this->_database->prepareAndExecute($moveThis, $params);

        Http::sendNoContent();
    }
}


// Útƒ-8 encoded
