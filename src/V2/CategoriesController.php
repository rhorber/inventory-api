<?php

/**
 * Class CategoriesController.
 *
 * @package Rhorber\Inventory\API\V2
 * @author  Raphael Horber
 * @version 30.11.2019
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
 * @version 30.11.2019
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
     * @version 29.11.2019
     */
    public function moveDown(int $categoryId)
    {
        $this->_moveCategory($categoryId, "+");
    }

    /**
     * Moves the category one position up.
     *
     * @param integer $categoryId ID of the category to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 29.11.2019
     */
    public function moveUp(int $categoryId)
    {
        $this->_moveCategory($categoryId, "-");
    }

    /**
     * Moves the category one position up or down.
     *
     * @param integer $categoryId ID of the category to move.
     * @param string  $direction  Direction of this category ("+" or "-" for down or up respectively).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 30.11.2019
     */
    private function _moveCategory(int $categoryId, string $direction)
    {
        $positionQuery  = "SELECT position FROM categories WHERE id = :id";
        $positionParams = [':id' => $categoryId];

        $positionStatement = $this->_database->prepareAndExecute($positionQuery, $positionParams);
        $thisCategory      = $positionStatement->fetch();

        // Calculate the positions.
        // `eval` can safely be used because `$direction` comes from a trusted source.
        $thisPosition   = $thisCategory['position'];
        $otherPosition  = eval("return ".$thisPosition." ".$direction." 1;");
        $positionParams = [
            ':thisPosition'  => $thisPosition,
            ':otherPosition' => $otherPosition,
        ];

        $moveOtherQuery = "
            UPDATE categories SET
                position = :thisPosition
            WHERE position = :otherPosition
        ";
        $this->_database->prepareAndExecute($moveOtherQuery, $positionParams);

        $moveThisQuery  = "
            UPDATE categories SET
                position = :otherPosition
            WHERE id = :id
        ";
        $moveThisParams = [
            ':id'            => $categoryId,
            ':otherPosition' => $otherPosition,
        ];
        $this->_database->prepareAndExecute($moveThisQuery, $moveThisParams);

        $responseQuery     = "
            SELECT *
            FROM categories
            WHERE position IN(:thisPosition, :otherPosition)
        ";
        $responseStatement = $this->_database->prepareAndExecute($responseQuery, $positionParams);
        $categories        = $responseStatement->fetchAll();

        $response = ['categories' => $categories];
        Http::sendJsonResponse($response);
    }
}


// Útƒ-8 encoded
