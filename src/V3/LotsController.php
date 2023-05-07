<?php

/**
 * Class LotsController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API\V3;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Helpers;
use Rhorber\Inventory\API\Http;
use Rhorber\Inventory\API\V3\Entities\Lot;


/**
 * Class for modifying or adding a lot (part or complete stock of an article). All methods terminate execution.
 *
 * To keep it simple (circumvent conversions) and provide compatibility between ECMAScript and MongoDB
 * the following design decisions were made:
 * - The current timestamp (last update) of a lot is stored as an integer (in seconds).
 * - PHP provides/sets the update values.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 01.05.2023
 */
class LotsController
{
    /**
     * Database connection.
     *
     * @access private
     * @var    Database
     */
    private $_database;

    /**
     * Collection `lots` of the database.
     *
     * @access private
     * @var    \MongoDB\Collection
     */
    private $_lots;


    /**
     * Initializes a new instance of the `LotsController` class.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function __construct()
    {
        $this->_database = new Database();
        $this->_lots     = $this->_database->lots;
    }

    /**
     * Adds a new lot from payload.
     *
     * This endpoint is not used by the current client app.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function createLot()
    {
        $payload = Helpers::getPayload();

        $lotId     = $this->_database->getNextValue(
            $this->_lots,
            "_id"
        );
        $position  = $this->_database->getNextValue(
            $this->_lots,
            "position",
            ['article' => $payload['article']]
        );
        $timestamp = $payload['timestamp'] ?? $this->_database->nowTimestamp;

        $document = [
            '_id'        => $lotId,
            'article'    => $payload['article'],
            'bestBefore' => $payload['best_before'],
            'stock'      => $payload['stock'],
            'position'   => $position,
            'timestamp'  => $timestamp,
        ];
        $this->_lots->insertOne($document);

        Http::sendNoContent();
    }

    /**
     * Updates the lot from payload.
     *
     * This endpoint is not used by the current client app.
     *
     * @param integer $lotId ID of the lot to update.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function updateLot(int $lotId)
    {
        $payload = Helpers::getPayload();

        $timestamp = $this->_database->getNewTimestamp(
            $this->_lots,
            $lotId,
            $payload['timestamp']
        );

        // Update only if the lot was not updated since this cached update.
        if ($timestamp === false) {
            Http::sendNoContent();
        }

        $updateFields = [
            'bestBefore' => $payload['best_before'],
            'stock'      => $payload['stock'],
            'timestamp'  => $timestamp,
        ];
        $this->_lots->updateOne(
            ['_id' => $lotId],
            ['$set' => $updateFields]
        );

        Http::sendNoContent();
    }

    /**
     * Decrements the lot's stock by one.
     *
     * @param integer $lotId ID of the lot to modify.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function decrementStock(int $lotId)
    {
        $this->_modifyStock($lotId, -1);
    }

    /**
     * Increments the lot's stock by one.
     *
     * @param integer $lotId ID of the lot to modify.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function incrementStock(int $lotId)
    {
        $this->_modifyStock($lotId, 1);
    }

    /**
     * Moves the lot one position down.
     *
     * This endpoint is not used by the current client app.
     *
     * @param integer $lotId ID of the lot to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function moveDown(int $lotId)
    {
        $this->_moveLot($lotId, false);
    }

    /**
     * Moves the lot one position up.
     *
     * This endpoint is not used by the current client app.
     *
     * @param integer $lotId ID of the lot to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function moveUp(int $lotId)
    {
        $this->_moveLot($lotId, true);
    }

    /**
     * Shorthand method for modifying the lot's stock.
     *
     * @param integer $lotId  ID of the lot to modify.
     * @param int     $amount Increment the stock by this amount (can be positive or negative).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    private function _modifyStock(int $lotId, int $amount)
    {
        /** @var \MongoDB\Model\BSONDocument $lot */
        $update = [
            '$inc' => ['stock' => $amount],
            '$set' => ['timestamp' => $this->_database->nowTimestamp],
        ];
        $lot    = $this->_lots->findOneAndUpdate(
            ['_id' => $lotId],
            $update,
            ['returnDocument' => Database::$returnDocumentAfter]
        );

        $response = Lot::mapToEntity($lot);

        Http::sendJsonResponse($response);
    }

    /**
     * Moves the lot one position up or down.
     *
     * @param integer $lotId  ID of the lot to move.
     * @param boolean $moveUp Whether to move the document up (decrease position).
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    private function _moveLot(int $lotId, bool $moveUp)
    {
        // Get properties of the lot to move.
        $lot = $this->_lots->findOne(
            ['_id' => $lotId],
            ['projection' => ['article' => 1, 'position' => 1]]
        );

        // Move lots.
        $filter = [
            'article' => $lot['article'],
        ];
        $lots   = $this->_database->moveDocument(
            $this->_lots,
            $lotId,
            $lot['position'],
            $moveUp,
            $filter
        );

        // Proceed only if any lots were moved.
        if ($lots === false) {
            Http::sendBadRequest();
        }

        // Return the updated lots.
        $entities = Lot::mapToEntities($lots);
        $response = ['lots' => $entities];

        Http::sendJsonResponse($response);
    }
}


// Útƒ-8 encoded
