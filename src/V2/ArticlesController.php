<?php

/**
 * Class ArticlesController.
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
 * Class for modifying or adding an article. All methods terminate execution.
 *
 * @package Rhorber\Inventory\API\V1
 * @author  Raphael Horber
 * @version 23.11.2019
 */
class ArticlesController
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
     * Returns all articles as JSON response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function returnAllArticles()
    {
        $query    = "SELECT * FROM articles";
        $articles = $this->_database->queryAndFetch($query);

        $response = ['articles' => $articles];
        Http::sendJsonResponse($response);
    }

    /**
     * Returns the article as JSON response.
     *
     * @param integer $articleId ID of the article to return.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function returnArticle(int $articleId)
    {
        $query  = "SELECT * FROM articles WHERE id = :id";
        $params = [':id' => $articleId];

        $statement = $this->_database->prepareAndExecute($query, $params);
        $articles  = $statement->fetch();

        Http::sendJsonResponse($articles);
    }

    /**
     * Adds a new article from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function createArticle()
    {
        $payload  = Helpers::getSanitizedPayload();
        $position = $this->_getNextPositionInCategory($payload['category']);

        $insertQuery  = "
            INSERT INTO articles (
                category, name, size, unit, best_before, stock, position
            ) VALUES (
                :category, :name, :size, :unit, :best_before, :stock, :position
            )
        ";
        $insertParams = [
            ':category'    => $payload['category'],
            ':name'        => $payload['name'],
            ':size'        => $payload['size'],
            ':unit'        => $payload['unit'],
            ':best_before' => $payload['best_before'],
            ':stock'       => $payload['stock'],
            ':position'    => $position,
        ];

        $this->_database->prepareAndExecute($insertQuery, $insertParams);
        Http::sendNoContent();
    }

    /**
     * Updates the article from payload.
     *
     * @param integer $articleId ID of the article to modify.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function updateArticle(int $articleId)
    {
        $payload = Helpers::getSanitizedPayload();

        $categoryQuery  = "SELECT category FROM articles WHERE id = :id";
        $categoryParams = [':id' => $articleId];

        $categoryStatement = $this->_database->prepareAndExecute($categoryQuery, $categoryParams);
        $categoryRow       = $categoryStatement->fetch();
        $currentCategory   = $categoryRow['category'];

        if ($currentCategory != $payload['category']) {
            $category = $payload['category'];
            $position = $this->_getNextPositionInCategory($category);

            $moveQuery  = "
                UPDATE articles SET
                    category = :category,
                    position = :position
                WHERE id = :id
            ";
            $moveParams = [
                ':id'       => $articleId,
                ':category' => $category,
                ':position' => $position,
            ];

            $this->_database->prepareAndExecute($moveQuery, $moveParams);
        }

        $updateQuery  = "
            UPDATE articles SET
                name = :name,
                size = :size,
                unit = :unit,
                best_before = :best_before,
                stock = :stock
            WHERE id = :id
        ";
        $updateParams = [
            ':id'          => $articleId,
            ':name'        => $payload['name'],
            ':size'        => $payload['size'],
            ':unit'        => $payload['unit'],
            ':best_before' => $payload['best_before'],
            ':stock'       => $payload['stock'],
        ];

        $this->_database->prepareAndExecute($updateQuery, $updateParams);

        Http::sendNoContent();
    }

    /**
     * Decrements the article's stock by one.
     *
     * @param integer $articleId ID of the article to modify.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function decrementStock(int $articleId)
    {
        $this->_modifyStock($articleId, "stock - 1");
    }

    /**
     * Increments the article's stock by one.
     *
     * @param integer $articleId ID of the article to modify.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function incrementStock(int $articleId)
    {
        $this->_modifyStock($articleId, "stock + 1");
    }

    /**
     * Resets the article (zero stock, no best_before).
     *
     * @param integer $articleId ID of the article to reset.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function resetArticle(int $articleId)
    {
        $moveQuery  = "
            UPDATE articles SET
                stock = 0,
                best_before = ''
            WHERE id = :id
        ";
        $moveParams = [
            ':id' => $articleId,
        ];

        $this->_database->prepareAndExecute($moveQuery, $moveParams);
        Http::sendNoContent();
    }

    /**
     * Moves the article one position down.
     *
     * @param integer $articleId ID of the article to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function moveDown(int $articleId)
    {
        $this->_moveItem($articleId, ">", "ASC");
    }

    /**
     * Moves the article one position up.
     *
     * @param integer $articleId ID of the article to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    public function moveUp(int $articleId)
    {
        $this->_moveItem($articleId, "<", "DESC");
    }

    /**
     * Gets the next position (MAX + 1) in the category.
     *
     * @param integer $categoryId ID of the category to get the position from.
     *
     * @return integer
     */
    private function _getNextPositionInCategory(int $categoryId): int
    {
        $maxQuery  = "
            SELECT COALESCE(MAX(position), 0) + 1 AS new_position
            FROM articles
            WHERE category = :category
        ";
        $maxParams = [':category' => $categoryId];

        $maxStatement = $this->_database->prepareAndExecute($maxQuery, $maxParams);
        $maxRow       = $maxStatement->fetch();
        $position     = $maxRow['new_position'];

        return $position;
    }

    /**
     * Shorthand method for modifying the article's stock.
     *
     * @param integer $articleId ID of the article to modify.
     * @param string  $newStock  Stock's new value.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    private function _modifyStock(int $articleId, string $newStock)
    {
        $query  = "UPDATE articles SET stock = ".$newStock." WHERE id = :id";
        $params = [':id' => $articleId];

        $this->_database->prepareAndExecute($query, $params);
        Http::sendNoContent();
    }

    /**
     * Moves the article one position up or down.
     *
     * @param integer $articleId       ID of the article to move.
     * @param string  $compareOperator Comparator to use to find the other article to swap with
     *                                 (">" or "<" for down or up respectively).
     * @param string  $sortDirection   Sort direction to use to find the other article to swap with
     *                                 ("ASC" or "DESC" for down or up respectively).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 23.11.2019
     */
    private function _moveItem(int $articleId, string $compareOperator, string $sortDirection)
    {
        $queryThisQuery  = "
            SELECT category, position
            FROM articles
            WHERE id = :id
        ";
        $queryThisParams = [':id' => $articleId];

        $thisStatement = $this->_database->prepareAndExecute($queryThisQuery, $queryThisParams);
        $thisArticle   = $thisStatement->fetch();

        // other direction: position > :position, order by position asc
        $queryOtherQuery  = "
            SELECT id, position
            FROM articles
            WHERE category = :category
                AND position ".$compareOperator." :position
            ORDER BY position ".$sortDirection."
            LIMIT 1
        ";
        $queryOtherParams = [
            ':category' => $thisArticle['category'],
            ':position' => $thisArticle['position'],
        ];

        $otherStatement = $this->_database->prepareAndExecute($queryOtherQuery, $queryOtherParams);
        $otherArticle   = $otherStatement->fetch();

        $moveQuery     = "
            UPDATE articles SET
                position = :position
            WHERE id = :id
        ";
        $moveStatement = $this->_database->prepare($moveQuery);

        $moveThisParams = [
            ':id'       => $articleId,
            ':position' => $otherArticle['position'],
        ];
        $moveStatement->execute($moveThisParams);

        $moveOtherParams = [
            ':id'       => $otherArticle['id'],
            ':position' => $thisArticle['position'],
        ];
        $moveStatement->execute($moveOtherParams);

        Http::sendNoContent();
    }
}


// Útƒ-8 encoded
