<?php

/**
 * Class ArticlesController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API\V3;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Helpers;
use Rhorber\Inventory\API\Http;
use Rhorber\Inventory\API\V3\Entities\Article;


/**
 * Class for modifying or adding an article. All methods terminate execution.
 *
 * To keep it simple (circumvent conversions) and provide compatibility between ECMAScript and MongoDB
 * the following design decisions were made:
 * - The current timestamp (last update) of an article is stored as an integer (in seconds).
 * - PHP provides/sets the update values.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 01.05.2023
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
     * Collection `articles` of the database.
     *
     * @access private
     * @var    \MongoDB\Collection
     */
    private $_articles;


    /**
     * Returns the articles matching the filter as an array of Article entities.
     *
     * It searches the articles collection with the passed filter,
     * maps the found documents to entities and returns the resulting array.
     *
     * @param Database $database Database connection.
     * @param array    $filter   Filter articles by this query. This value is passed to the `$match` aggregation stage.
     *
     * @return  Article[] Array of Article entities, with properties `lots` and `gtins` set.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public static function getArticleEntities(Database $database, array $filter)
    {
        $lookup = [
            'from'         => "lots",
            'localField'   => "_id",
            'foreignField' => "article",
            'as'           => "lots",
        ];
        $cursor = $database->articles->aggregate([
            ['$match' => $filter],
            ['$lookup' => $lookup],
        ]);

        return Article::mapToEntities($cursor);
    }

    /**
     * Initializes a new instance of the `ArticlesController` class.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function __construct()
    {
        $this->_database = new Database();
        $this->_articles = $this->_database->articles;
    }

    /**
     * Returns all articles as JSON response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function returnAllArticles()
    {
        $filter   = [
            '_id' => ['$exists' => true],
        ];
        $entities = self::getArticleEntities(
            $this->_database,
            $filter
        );

        $response = ['articles' => $entities];

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
     * @version 01.05.2023
     */
    public function returnArticle(int $articleId)
    {
        $filter   = [
            '_id' => $articleId,
        ];
        $entities = self::getArticleEntities(
            $this->_database,
            $filter
        );

        if (count($entities) <= 0) {
            Http::sendNotFound();
        }

        $response = array_pop($entities);

        Http::sendJsonResponse($response);
    }

    /**
     * Adds a new article from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function createArticle()
    {
        $payload = Helpers::getPayload();

        $articleId   = $this->_database->getNextValue(
            $this->_articles,
            "_id"
        );
        $inventoried = $this->_getInventoriedStatus();
        $position    = $this->_getNextPositionInCategory($payload['category']);
        $timestamp   = $payload['timestamp'] ?? $this->_database->nowTimestamp;

        $document = [
            '_id'         => $articleId,
            'category'    => $payload['category'],
            'name'        => $payload['name'],
            'size'        => $payload['size'],
            'unit'        => $payload['unit'],
            'gtins'       => $payload['gtins'],
            'inventoried' => $inventoried,
            'position'    => $position,
            'timestamp'   => $timestamp,
        ];
        $this->_articles->insertOne($document);

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
     * @version 01.05.2023
     */
    public function updateArticle(int $articleId)
    {
        $payload = Helpers::getPayload();

        $timestamp = $this->_database->getNewTimestamp(
            $this->_articles,
            $articleId,
            $payload['timestamp']
        );

        // Update only if the article was not updated since this cached update.
        if ($timestamp === false) {
            Http::sendNoContent();
        }

        $currentArticle = $this->_articles->findOne(
            ['_id' => $articleId],
            ['projection' => ['category' => 1, 'position' => 1]]
        );

        $inventoried = $this->_getInventoriedStatus();

        if ($currentArticle['category'] != $payload['category']) {
            $articlePosition = $this->_getNextPositionInCategory($payload['category']);
        } else {
            $articlePosition = $currentArticle['position'];
        }

        $updateFields = [
            'category'    => $payload['category'],
            'name'        => $payload['name'],
            'size'        => $payload['size'],
            'unit'        => $payload['unit'],
            'gtins'       => $payload['gtins'],
            'inventoried' => $inventoried,
            'position'    => $articlePosition,
            'timestamp'   => $timestamp,
        ];
        $this->_articles->updateOne(
            ['_id' => $articleId],
            ['$set' => $updateFields]
        );

        if (isset($payload['lots'])) {
            $this->_database->lots->deleteMany(
                ['article' => $articleId]
            );

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
     * @version 01.05.2023
     */
    public function resetArticle(int $articleId)
    {
        $inventoried = $this->_getInventoriedStatus();

        $this->_database->lots->deleteMany(
            ['article' => $articleId]
        );

        $updateFields = [
            'inventoried' => $inventoried,
            'timestamp'   => $this->_database->nowTimestamp,
        ];
        $this->_articles->updateOne(
            ['_id' => $articleId],
            ['$set' => $updateFields]
        );

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
     * @version 01.05.2023
     */
    public function moveDown(int $articleId)
    {
        $this->_moveArticle($articleId, false);
    }

    /**
     * Moves the article one position up.
     *
     * @param integer $articleId ID of the article to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function moveUp(int $articleId)
    {
        $this->_moveArticle($articleId, true);
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
     * @version 01.05.2023
     */
    private function _getNextPositionInCategory(int $categoryId): int
    {
        $position = $this->_database->getNextValue(
            $this->_articles,
            "position",
            ['category' => $categoryId]
        );

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
     * @version 01.05.2023
     */
    private function _insertLots(int $articleId, array $lots)
    {
        if (empty($lots)) {
            return;
        }

        $lotId = $this->_database->getNextValue(
            $this->_database->lots,
            "_id"
        );

        $documents = [];
        $position  = 1;
        foreach ($lots as $lot) {
            $timestamp = $lot['timestamp'] ?? $this->_database->nowTimestamp;

            $document    = [
                '_id'        => $lotId,
                'article'    => $articleId,
                'bestBefore' => $lot['best_before'],
                'stock'      => $lot['stock'],
                'position'   => $position,
                'timestamp'  => $timestamp,
            ];
            $documents[] = $document;

            $lotId++;
            $position++;
        }

        $this->_database->lots->insertMany($documents);
    }

    /**
     * Moves the article one position up or down.
     *
     * @param integer $articleId ID of the article to move.
     * @param boolean $moveUp    Whether to move the document up (decrease position).
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    private function _moveArticle(int $articleId, bool $moveUp)
    {
        // Get properties of the article to move.
        $article = $this->_articles->findOne(
            ['_id' => $articleId],
            ['projection' => ['category' => 1, 'position' => 1]]
        );

        // Move articles.
        $filter   = [
            'category' => $article['category'],
        ];
        $articles = $this->_database->moveDocument(
            $this->_articles,
            $articleId,
            $article['position'],
            $moveUp,
            $filter
        );

        // Proceed only if any articles were moved.
        if ($articles === false) {
            Http::sendBadRequest();
        }

        // Return the updated articles.
        $idFilter = [
            '$in' => array_column($articles, '_id'),
        ];
        $filter   = [
            '_id' => $idFilter,
        ];
        $entities = self::getArticleEntities(
            $this->_database,
            $filter
        );

        $response = ['articles' => $entities];

        Http::sendJsonResponse($response);
    }
}


// Útƒ-8 encoded
