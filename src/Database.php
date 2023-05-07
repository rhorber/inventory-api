<?php

/**
 * Class Database.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Operation\FindOneAndUpdate;


/**
 * MongoDB wrapper. Handles database connection and operations, and contains convenience properties and methods.
 *
 * @property-read Collection  $articles     Collection `articles` of the database.
 * @property-read Collection  $categories   Collection `categories` of the database.
 * @property-read Collection  $inventories  Collection `inventories` of the database.
 * @property-read Collection  $logs         Collection `logs` of the database.
 * @property-read Collection  $lots         Collection `lots` of the database.
 * @property-read integer     $nowTimestamp Current time/now as integer (value of `time()`).
 * @property-read UTCDateTime $nowDateTime  Current time/now as {@see UTCDateTime}.
 * @property-read Collection  $tokens       Collection `tokens` of the database.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.05.2023
 */
class Database
{
    /**
     * `returnDocument` option value to return the document after the update is applied,
     *  {@see Collection::findOneAndUpdate()}.
     *
     * @access private
     * @var    integer
     */
    public static int $returnDocumentAfter = FindOneAndUpdate::RETURN_DOCUMENT_AFTER;

    /**
     * MongoDB client.
     *
     * @access private
     * @var    Client
     */
    private Client $_client;

    /**
     * MongoDB database.
     *
     * @access private
     * @var    \MongoDB\Database
     */
    private \MongoDB\Database $_database;

    /**#@+
     * @access private
     * @var    Collection
     */
    /** Collection `articles` of the database (initialized at first use). */
    private Collection $_articles;
    /** Collection `categories` of the database (initialized at first use). */
    private Collection $_categories;
    /** Collection `inventories` of the database (initialized at first use). */
    private Collection $_inventories;
    /** Collection `logs` of the database (initialized at first use). */
    private Collection $_logs;
    /** Collection `lots` of the database (initialized at first use). */
    private Collection $_lots;
    /** Collection `tokens` of the database (initialized at first use). */
    private Collection $_tokens;
    /**#@-*/

    /**
     * Current time/now as integer (value of `time()`, initialized at first use).
     *
     * @access private
     * @var    integer
     */
    private $_nowTimestamp;

    /**
     * Current time/now as {@see UTCDateTime} (initialized at first use).
     *
     * @access private
     * @var    UTCDateTime
     */
    private $_nowDateTime;


    /**
     * Initializes a new instance of the `Database` class.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function __construct()
    {
        if (isset($_ENV['DATABASE_URI']) === false || isset($_ENV['DATABASE_NAME']) === false) {
            Http::sendServerError();
        }

        $uri  = $_ENV['DATABASE_URI'];
        $name = $_ENV['DATABASE_NAME'];

        $this->_client   = new Client($uri);
        $this->_database = $this->_client->selectDatabase($name);
    }

    /**
     * Lazy initializes the properties not used by every controller.
     *
     * @param string $name Name of the property to get.
     *
     * @return  integer|UTCDateTime|Collection The value of the requested property.
     * @access  public
     * @throws  \Exception If `$name` is an unknown property.
     * @version 01.05.2023
     * @author  Raphael Horber
     */
    public function __get(string $name)
    {
        switch ($name) {
            case "articles":
                $this->_articles ??= $this->_database->selectCollection("articles");
                return $this->_articles;

            case "categories":
                $this->_categories ??= $this->_database->selectCollection("categories");
                return $this->_categories;

            case "inventories":
                $this->_inventories ??= $this->_database->selectCollection("inventories");
                return $this->_inventories;

            case "logs":
                $this->_logs ??= $this->_database->selectCollection("logs");
                return $this->_logs;

            case "lots":
                $this->_lots ??= $this->_database->selectCollection("lots");
                return $this->_lots;

            case "nowTimestamp":
                $this->_nowTimestamp ??= time();
                return $this->_nowTimestamp;

            case "nowDateTime":
                $this->_nowDateTime ??= new UTCDateTime();
                return $this->_nowDateTime;

            case "tokens":
                $this->_tokens ??= $this->_database->selectCollection("tokens");
                return $this->_tokens;
        }

        throw new \Exception("Undefined property '".$name."'.");
    }

    /**
     * Returns the next value (max + 1) of `$field` in the collection, optionally filtering by `$filter`.
     *
     * This method can be used to get the id for a new document or to get the next position.
     * To get the position in a subset, the filter can be set. E.g. to `['category' => $categoryId]`.
     *
     * @param Collection $collection Collection containing the documents to query.
     * @param string     $field      Field to get the next value of (i.e. `MAX($field) + 1`).
     * @param array      $filter     Filter to use before getting the next value, or `[]` to not filter at all.
     *
     * @return  integer `MAX($field)` of documents in `$collection` matching `$filter` or `1` if no document matches.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function getNextValue(Collection $collection, string $field, array $filter = [])
    {
        $document = $collection->findOne(
            $filter,
            ['sort' => [$field => -1]]
        );

        if ($document !== null) {
            return $document[$field] + 1;
        } else {
            return 1;
        }
    }

    /**
     * Returns an array containing the number of documents matching `$filter` and `$fieldName` of the first document.
     *
     * It filters the documents in `$collection` by `$filter`.
     * Then, creates a document with the two fields `count` and `$fieldName`.
     * `count` contains the number of documents matching `$filter` and
     * `$fieldName` the value of the first document's `$fieldName` field.
     * If no document matches `$filter` then `['count' => 0, $fieldName => null]` is returned.
     * <code>
     * // For example a call like:
     * getCountAndFirstDocument($db->tokens, ['active' => true], 'name');
     * // could return:
     * ['count' => 1, 'name' => 'PC']
     * // or when no document matches:
     * ['count' => 0, 'name' => null]
     * </code>
     *
     * @param Collection $collection Collection containing the documents to count.
     * @param array      $filter     Filter documents by this query.
     *                               This value is passed to the `$match` aggregation stage.
     * @param string     $fieldName  Field of the first matching document to return.
     *
     * @return  array An array with the number of documents matching `$filter` and `$fieldName` of the first document.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function getCountAndFirstField(Collection $collection, array $filter, string $fieldName = "")
    {
        $group     = [
            '_id'   => null,
            'count' => ['$sum' => 1],
            'value' => ['$first' => "\$".$fieldName],
        ];
        $addFields = [
            $fieldName => "\$value",
        ];
        $cursor    = $collection->aggregate([
            ['$match' => $filter],
            ['$group' => $group],
            ['$addFields' => $addFields],
        ]);
        $documents = $cursor->toArray();

        if (count($documents) === 1) {
            return array_pop($documents);
        } else {
            return [
                'count'    => 0,
                $fieldName => null,
            ];
        }
    }

    /**
     * Returns the new timestamp to set on the document on update, or `false` if the update should be skipped.
     *
     * In case `$timestamp` is unset, {@see Database::$nowTimestamp} is returned.
     * When it is set the timestamp of the specified document is fetched and compared against `$timestamp`.
     * If the document's timestamp is higher (it was updated since `$timestamp`),
     * `false` is returned and no update should be made (update is outdated), else `$timestamp` is returned.
     *
     * @param Collection   $collection Collection containing the document being updated.
     * @param integer      $documentId ID of the document being updated.
     * @param integer|null $timestamp  Timestamp to check against, can be unset.
     *
     * @return  integer|false The new timestamp to set on the document,
     *                        or `false` if the document should not be updated.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function getNewTimestamp(Collection $collection, int $documentId, ?int $timestamp)
    {
        if (isset($timestamp) === false) {
            return $this->nowTimestamp;
        }

        $document = $collection->findOne(
            ['_id' => $documentId],
            ['projection' => ['timestamp' => 1]]
        );

        if ($timestamp < $document['timestamp']) {
            return false;
        }

        return $timestamp;
    }

    /**
     * Moves the document in the collection with the given id in one position up or down.
     *
     * First it determines the new/target position of the document to move with `$documentPosition` and `$moveUp`.
     * Then, it searches for a document on that new/target position. If no document was found, `false` is returned.
     * If a document was found, the positions of the two documents (passed one and found one) are swapped.
     * That is, the passed document gets the new position and the found on gets the current.
     * At the end, the two updated/moved documents are returned.
     *
     * @param Collection $collection       Collection containing the documents to move.
     * @param integer    $documentId       ID of the document to move.
     * @param integer    $documentPosition Current position of the document to move.
     * @param boolean    $moveUp           Whether to move the document up (decrease position).
     * @param array      $documentFilter   Filter to use when searching the document to swap with.
     *
     * @return  array|false The two updated/moved documents, or `false` if no document was found on the target position.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function moveDocument(
        Collection $collection,
        int $documentId,
        int $documentPosition,
        bool $moveUp,
        array $documentFilter
    ) {
        // Determine properties to find the other document.
        if ($moveUp === true) {
            $sortDirection   = -1;
            $compareOperator = "\$lt";
        } else {
            $sortDirection   = 1;
            $compareOperator = "\$gt";
        }

        // Search for a document on the target position.
        $otherFilter   = [
            ...$documentFilter,
            'position' => [$compareOperator => $documentPosition],
        ];
        $otherOptions  = [
            'projection' => ['_id' => 1, 'position' => 1],
            'sort'       => ['position' => $sortDirection],
        ];
        $otherDocument = $collection->findOne(
            $otherFilter,
            $otherOptions
        );

        // Proceed only if a document was found.
        if ($otherDocument === null) {
            return false;
        }

        // Move our document to the target position.
        $thisUpdateFields = [
            'position'  => $otherDocument['position'],
            'timestamp' => $this->nowTimestamp,
        ];
        $thisDocument     = $collection->findOneAndUpdate(
            ['_id' => $documentId],
            ['$set' => $thisUpdateFields],
            ['returnDocument' => self::$returnDocumentAfter]
        );

        // Move the other document from the target position to our current position.
        $otherUpdateFields = [
            'position' => $documentPosition,
        ];
        $otherDocument     = $collection->findOneAndUpdate(
            ['_id' => $otherDocument['_id']],
            ['$set' => $otherUpdateFields],
            ['returnDocument' => self::$returnDocumentAfter]
        );

        // Return the two updated documents.
        return [
            $thisDocument,
            $otherDocument,
        ];
    }

    /**
     * Creates a new log entry (put document into `logs` collection).
     *
     * @param string $type    Type of the log entry to create ("error", "request", ...).
     * @param string $content Content of the log entry to create.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public static function newLogEntry(string $type, string $content)
    {
        $database = new Database();

        $document = [
            'timestamp'  => $database->nowDateTime,
            'type'       => $type,
            'content'    => $content,
            'clientName' => Authorization::getClientName(),
            'clientIp'   => $_SERVER['REMOTE_ADDR'],
            'userAgent'  => $_SERVER['HTTP_USER_AGENT'],
        ];
        $database->logs->insertOne($document);
    }
}


// Útƒ-8 encoded
