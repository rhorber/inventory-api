<?php

/**
 * Class ArticlesController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 14.11.2021
 */
namespace Rhorber\Inventory\API\V3;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Helpers;
use Rhorber\Inventory\API\Http;


/**
 * Class for modifying or adding an article. All methods terminate execution.
 *
 * To keep it simple (circumvent conversions) and provide compatibility between ECMAScript, PostgreSQL and MySQL
 * the following design decisions were made:
 * - The current timestamp (last update) of an article is stored as an integer (in seconds).
 * - PHP provides/sets the update values.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 14.11.2021
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
     * @version 05.08.2020
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
     * @version 05.08.2020
     */
    public function returnAllArticles()
    {
        $articlesQuery = "SELECT * FROM articles";
        $articlesRows  = $this->_database->queryAndFetch($articlesQuery);

        $articles      = [];
        $lotsQuery     = "SELECT * FROM lots WHERE article = :id";
        $lotsStatement = $this->_database->prepare($lotsQuery);

        foreach ($articlesRows as $article) {
            $lotsParams      = [':id' => $article['id']];
            $article['lots'] = $this->_database->executeAndFetchAll($lotsStatement, $lotsParams);

            $articles[] = $article;
        }

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
     * @version 20.08.2020
     */
    public function returnArticle(int $articleId)
    {
        $article = $this->_getArticle($articleId);

        Http::sendJsonResponse($article);
    }

    /**
     * Adds a new article from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 14.11.2021
     */
    public function createArticle()
    {
        $payload = Helpers::getSanitizedPayload();

        $inventoried = $this->_getInventoriedStatus();
        $position    = $this->_getNextPositionInCategory($payload['category']);
        $timestamp   = $payload['timestamp'] ?? time();

        $insertQuery  = "
            INSERT INTO articles (
                category, name, size, unit, gtin, inventoried, position, timestamp
            ) VALUES (
                :category, :name, :size, :unit, :gtin, :inventoried, :position, :timestamp
            )
        ";
        $insertParams = [
            ':category'    => $payload['category'],
            ':name'        => $payload['name'],
            ':size'        => $payload['size'],
            ':unit'        => $payload['unit'],
            ':gtin'        => $payload['gtin'],
            ':inventoried' => $inventoried,
            ':position'    => $position,
            ':timestamp'   => $timestamp,
        ];
        $this->_database->prepareAndExecute($insertQuery, $insertParams);

        $articleId = $this->_database->lastInsertId();

        if (isset($payload['lots'])) {
            $this->_insertLots($articleId, $payload['lots']);
        }

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
     * @version 14.11.2021
     */
    public function updateArticle(int $articleId)
    {
        $payload = Helpers::getSanitizedPayload();

        $currentQuery  = "SELECT category, position, timestamp FROM articles WHERE id = :id";
        $currentParams = [':id' => $articleId];

        $currentStatement = $this->_database->prepareAndExecute($currentQuery, $currentParams);
        $currentArticle   = $currentStatement->fetch();

        $inventoried = $this->_getInventoriedStatus();

        if ($currentArticle['category'] != $payload['category']) {
            $articlePosition = $this->_getNextPositionInCategory($payload['category']);
        } else {
            $articlePosition = $currentArticle['position'];
        }

        if (isset($payload['timestamp'])) {
            $timestamp = $payload['timestamp'];

            if ($timestamp < $currentArticle['timestamp']) {
                Http::sendNoContent();
            }
        } else {
            $timestamp = time();
        }

        $updateQuery  = "
            UPDATE articles SET
                category = :category,
                name = :name,
                size = :size,
                unit = :unit,
                gtin = :gtin,
                inventoried = :inventoried,
                position = :position,
                timestamp = :timestamp
            WHERE id = :id
        ";
        $updateParams = [
            ':id'          => $articleId,
            ':category'    => $payload['category'],
            ':name'        => $payload['name'],
            ':size'        => $payload['size'],
            ':unit'        => $payload['unit'],
            ':gtin'        => $payload['gtin'],
            ':inventoried' => $inventoried,
            ':position'    => $articlePosition,
            ':timestamp'   => $timestamp,
        ];
        $this->_database->prepareAndExecute($updateQuery, $updateParams);

        if (isset($payload['lots'])) {
            $deleteLotsQuery  = "
                DELETE FROM lots
                WHERE article = :id
            ";
            $deleteLotsParams = [':id' => $articleId];
            $this->_database->prepareAndExecute($deleteLotsQuery, $deleteLotsParams);

            $this->_insertLots($articleId, $payload['lots']);
        }

        Http::sendNoContent();
    }

    /**
     * Resets the article (no lots).
     *
     * @param integer $articleId ID of the article to reset.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 04.09.2020
     */
    public function resetArticle(int $articleId)
    {
        $payload  = Helpers::getSanitizedPayload();
        $idParams = [':id' => $articleId];

        if (isset($payload['timestamp'])) {
            $timestamp = $payload['timestamp'];

            $currentQuery     = "SELECT timestamp FROM articles WHERE id = :id";
            $currentStatement = $this->_database->prepareAndExecute($currentQuery, $idParams);
            $currentTimestamp = $currentStatement->fetchColumn(0);

            if ($timestamp < $currentTimestamp) {
                $this->returnArticle($articleId);
            }
        } else {
            $timestamp = time();
        }

        $deleteLotsQuery = "
            DELETE FROM lots
            WHERE article = :id
        ";
        $this->_database->prepareAndExecute($deleteLotsQuery, $idParams);

        $updateArticleQuery  = "
            UPDATE articles SET
                timestamp = :timestamp
            WHERE id = :id
        ";
        $updateArticleParams = [
            ':id'        => $articleId,
            ':timestamp' => $timestamp,
        ];
        $this->_database->prepareAndExecute($updateArticleQuery, $updateArticleParams);

        $this->returnArticle($articleId);
    }

    /**
     * Moves the article one position down.
     *
     * @param integer $articleId ID of the article to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    public function moveDown(int $articleId)
    {
        $this->_moveArticle($articleId, ">", "ASC");
    }

    /**
     * Moves the article one position up.
     *
     * @param integer $articleId ID of the article to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    public function moveUp(int $articleId)
    {
        $this->_moveArticle($articleId, "<", "DESC");
    }

    /**
     * Returns the article with its lots.
     *
     * @param integer $articleId ID of the article to return.
     *
     * @return  array Article row with an additional index `lots` containing its lots.
     * @access  private
     * @author  Raphael Horber
     * @version 20.08.2020
     */
    private function _getArticle(int $articleId): array
    {
        $params = [':id' => $articleId];

        $articleQuery     = "SELECT * FROM articles WHERE id = :id";
        $articleStatement = $this->_database->prepareAndExecute($articleQuery, $params);
        $article          = $articleStatement->fetch();

        $lotsQuery       = "SELECT * FROM lots WHERE article = :id";
        $lotsStatement   = $this->_database->prepareAndExecute($lotsQuery, $params);
        $article['lots'] = $lotsStatement->fetchAll();

        return $article;
    }

    /**
     * Gets status for the inventoried field, accordingly to inventories status.
     *
     * @return  integer
     * @access  private
     * @author  Raphael Horber
     * @version 12.08.2021
     */
    private function _getInventoriedStatus(): int
    {
        $active = InventoriesController::isInventoryActive($this->_database);

        if ($active) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * Gets the next position (MAX + 1) in the category.
     *
     * @param integer $categoryId ID of the category to get the position from.
     *
     * @return  integer
     * @access  private
     * @author  Raphael Horber
     * @version 05.08.2020
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
        $position     = $maxStatement->fetchColumn(0);

        return $position;
    }

    /**
     * Inserts the article's lots from the passed payload.
     *
     * @param integer $articleId ID of the article to insert the lots for.
     * @param array   $lots      Lots element from the payload.
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 12.08.2021
     */
    private function _insertLots(int $articleId, array $lots)
    {
        $query     = "
            INSERT INTO lots (
                article, best_before, stock, position, timestamp
            ) VALUES (
                :article, :best_before, :stock, :position, :timestamp
            )
        ";
        $statement = $this->_database->prepare($query);

        $position = 0;
        foreach ($lots as $lot) {
            $position++;
            $timestamp = $lot['timestamp'] ?? time();

            $params = [
                ':article'     => $articleId,
                ':best_before' => $lot['best_before'],
                ':stock'       => $lot['stock'],
                ':position'    => $position,
                ':timestamp'   => $timestamp,
            ];
            $statement->execute($params);
        }
    }

    /**
     * Moves the article one position up or down.
     *
     * @param integer $articleId       ID of the article to move.
     * @param string  $compareOperator Comparator used to find the other article to swap with
     *                                 (">" or "<" for down or up respectively).
     * @param string  $sortDirection   Sort direction used to find the other article to swap with
     *                                 ("ASC" or "DESC" for down or up respectively).
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 20.08.2020
     */
    private function _moveArticle(int $articleId, string $compareOperator, string $sortDirection)
    {
        $queryThisQuery  = "
            SELECT category, position
            FROM articles
            WHERE id = :id
        ";
        $queryThisParams = [':id' => $articleId];

        $thisStatement = $this->_database->prepareAndExecute($queryThisQuery, $queryThisParams);
        $thisArticle   = $thisStatement->fetch();

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

        $moveThisQuery     = "
            UPDATE articles SET
                position = :position,
                timestamp = :timestamp
            WHERE id = :id
        ";
        $moveThisStatement = $this->_database->prepare($moveThisQuery);

        $moveThisParams = [
            ':id'        => $articleId,
            ':position'  => $otherArticle['position'],
            ':timestamp' => time(),
        ];
        $moveThisStatement->execute($moveThisParams);

        $moveOtherQuery     = "
            UPDATE articles SET
                position = :position
            WHERE id = :id
        ";
        $moveOtherStatement = $this->_database->prepare($moveOtherQuery);

        $moveOtherParams = [
            ':id'       => $otherArticle['id'],
            ':position' => $thisArticle['position'],
        ];
        $moveOtherStatement->execute($moveOtherParams);

        $articles = [
            $this->_getArticle($articleId),
            $this->_getArticle($otherArticle['id']),
        ];

        $response = ['articles' => $articles];
        Http::sendJsonResponse($response);
    }
}


// Útƒ-8 encoded
