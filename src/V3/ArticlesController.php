<?php

/**
 * Class ArticlesController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 05.08.2020
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
 * @version 05.08.2020
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
     * @version 05.08.2020
     */
    public function returnArticle(int $articleId)
    {
        $params = [':id' => $articleId];

        $articleQuery     = "SELECT * FROM articles WHERE id = :id";
        $articleStatement = $this->_database->prepareAndExecute($articleQuery, $params);
        $article          = $articleStatement->fetch();

        $lotsQuery       = "SELECT * FROM lots WHERE article = :id";
        $lotsStatement   = $this->_database->prepareAndExecute($lotsQuery, $params);
        $article['lots'] = $lotsStatement->fetchAll();

        Http::sendJsonResponse($article);
    }

    /**
     * Adds a new article from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    public function createArticle()
    {
        $payload = Helpers::getSanitizedPayload();

        $position  = $this->_getNextPositionInCategory($payload['category']);
        $timestamp = $payload['timestamp'] ?? time();

        $insertQuery  = "
            INSERT INTO articles (
                category, name, size, unit, position, timestamp
            ) VALUES (
                :category, :name, :size, :unit, :position, :timestamp
            )
        ";
        $insertParams = [
            ':category'  => $payload['category'],
            ':name'      => $payload['name'],
            ':size'      => $payload['size'],
            ':unit'      => $payload['unit'],
            ':position'  => $position,
            ':timestamp' => $timestamp,
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
     * @version 05.08.2020
     */
    public function updateArticle(int $articleId)
    {
        $payload = Helpers::getSanitizedPayload();

        $currentQuery  = "SELECT category, position, timestamp FROM articles WHERE id = :id";
        $currentParams = [':id' => $articleId];

        $currentStatement = $this->_database->prepareAndExecute($currentQuery, $currentParams);
        $currentArticle   = $currentStatement->fetch();

        if (isset($payload['timestamp']) && $payload['timestamp'] < $currentArticle['timestamp']) {
            Http::sendNoContent();
        }

        if ($currentArticle['category'] != $payload['category']) {
            $articlePosition = $this->_getNextPositionInCategory($payload['category']);
        } else {
            $articlePosition = $currentArticle['position'];
        }

        $updateQuery  = "
            UPDATE articles SET
                category = :category,
                name = :name,
                size = :size,
                unit = :unit,
                position = :position,
                timestamp = :timestamp
            WHERE id = :id
        ";
        $updateParams = [
            ':id'        => $articleId,
            ':category'  => $payload['category'],
            ':name'      => $payload['name'],
            ':size'      => $payload['size'],
            ':unit'      => $payload['unit'],
            ':position'  => $articlePosition,
            ':timestamp' => time(),
        ];
        $this->_database->prepareAndExecute($updateQuery, $updateParams);

        if (isset($payload['lots'])) {
            $deleteLotsQuery  = "
                DELETE FROM lots
                WHERE article = :id
            ";
            $deleteLotsParams = [':id' => $articleId];
            $this->_database->prepareAndExecute($deleteLotsQuery, $deleteLotsParams);

            $insertLotQuery     = "
                INSERT INTO lots (
                    article, best_before, stock, position, timestamp
                ) VALUES (
                    :article, :best_before, :stock, :position, :timestamp
                )
            ";
            $insertLotStatement = $this->_database->prepare($insertLotQuery);

            $lotPosition = 0;
            foreach ($payload['lots'] as $lot) {
                $lotPosition++;
                $lotTimestamp = $lot['timestamp'] ?? time();

                $insertLotParams = [
                    ':article'     => $articleId,
                    ':best_before' => $lot['best_before'],
                    ':stock'       => $lot['stock'],
                    ':position'    => $lotPosition,
                    ':timestamp'   => $lotTimestamp,
                ];
                $insertLotStatement->execute($insertLotParams);
            }
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
     * @version 05.08.2020
     */
    public function resetArticle(int $articleId)
    {
        $payload  = Helpers::getSanitizedPayload();
        $idParams = [':id' => $articleId];

        if (isset($payload['timestamp'])) {
            $currentQuery = "SELECT timestamp FROM articles WHERE id = :id";

            $currentStatement = $this->_database->prepareAndExecute($currentQuery, $idParams);
            $currentTimestamp = $currentStatement->fetchColumn(0);

            if ($payload['timestamp'] < $currentTimestamp) {
                $this->returnArticle($articleId);
            }
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
            ':timestamp' => time(),
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
     * @version 05.08.2020
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

        $responseQuery     = "
            SELECT *
            FROM articles
            WHERE id IN(:thisId, :otherId)
        ";
        $responseParams    = [
            ':thisId'  => $articleId,
            ':otherId' => $otherArticle['id'],
        ];
        $responseStatement = $this->_database->prepareAndExecute($responseQuery, $responseParams);
        $articles          = $responseStatement->fetchAll();

        $response = ['articles' => $articles];
        Http::sendJsonResponse($response);
    }
}


// Útƒ-8 encoded
