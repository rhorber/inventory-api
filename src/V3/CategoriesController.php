<?php

/**
 * Class CategoriesController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 24.04.2022
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
 * @version 24.04.2022
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
     * @version 05.08.2020
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
     * @version 04.04.2022
     */
    public function returnAllCategories()
    {
        $query = "SELECT * FROM categories";
        $rows  = $this->_database->queryAndFetch($query);

        $categories = Category::mapToEntities($rows);
        $response   = ['categories' => $categories];

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
     * @version 04.04.2022
     */
    public function returnCategory(int $categoryId)
    {
        $query  = "SELECT * FROM categories WHERE id = :id";
        $params = [':id' => $categoryId];

        $statement = $this->_database->prepareAndExecute($query, $params);
        $resultRow = $statement->fetch();

        $category = Category::mapToEntity($resultRow);

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
     * @version 04.04.2022
     */
    public function returnArticles(int $categoryId)
    {
        $articlesQuery  = "SELECT * FROM articles WHERE category = :id";
        $articlesParams = [':id' => $categoryId];

        $articlesStatement = $this->_database->prepareAndExecute($articlesQuery, $articlesParams);
        $articlesRows      = $articlesStatement->fetchAll();

        $articles = ArticlesController::getArticlesWithLots($this->_database, $articlesRows);
        $response = ['articles' => $articles];

        Http::sendJsonResponse($response);
    }

    /**
     * Adds a new category from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 24.04.2022
     */
    public function createCategory()
    {
        $payload = Helpers::getPayload();

        $maxQuery  = "
            SELECT COALESCE(MAX(position), 0) + 1 AS new_position
            FROM categories
        ";
        $maxResult = $this->_database->queryAndFetch($maxQuery);

        $position  = $maxResult[0]['new_position'];
        $timestamp = $payload['timestamp'] ?? time();

        $insertQuery = "
            INSERT INTO categories (
                name, position, timestamp
            ) VALUES (
                :name, :position, :timestamp
            )
        ";
        $params      = [
            ':name'      => $payload['name'],
            ':position'  => $position,
            ':timestamp' => $timestamp,
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
     * @version 24.04.2022
     */
    public function updateCategory(int $categoryId)
    {
        $payload = Helpers::getPayload();

        if (isset($payload['timestamp'])) {
            $currentQuery  = "SELECT timestamp FROM categories WHERE id = :id";
            $currentParams = [':id' => $categoryId];

            $currentStatement = $this->_database->prepareAndExecute($currentQuery, $currentParams);
            $currentTimestamp = $currentStatement->fetchColumn(0);

            $timestamp = $payload['timestamp'];
            if ($timestamp < $currentTimestamp) {
                Http::sendNoContent();
            }
        } else {
            $timestamp = time();
        }

        $updateQuery  = "
            UPDATE categories SET
                name = :name,
                timestamp = :timestamp
            WHERE id = :id
        ";
        $updateParams = [
            ':id'        => $categoryId,
            ':name'      => $payload['name'],
            ':timestamp' => $timestamp,
        ];

        $this->_database->prepareAndExecute($updateQuery, $updateParams);
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
     * @version 05.08.2020
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
     * @version 05.08.2020
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
     * @version 04.04.2022
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
        $resultRows        = $responseStatement->fetchAll();

        $categories = Category::mapToEntities($resultRows);
        $response   = ['categories' => $categories];

        Http::sendJsonResponse($response);
    }
}


// Útƒ-8 encoded
