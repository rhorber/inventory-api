<?php

/**
 * Class LotsController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 04.09.2020
 */
namespace Rhorber\Inventory\API\V3;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Helpers;
use Rhorber\Inventory\API\Http;


/**
 * Class for modifying or adding a lot (part or complete stock of an article). All methods terminate execution.
 *
 * To keep it simple (circumvent conversions) and provide compatibility between ECMAScript, PostgreSQL and MySQL
 * the following design decisions were made:
 * - The current timestamp (last update) of a lot is stored as an integer (in seconds).
 * - PHP provides/sets the update values.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 04.09.2020
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
     * Adds a new lot from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    public function createLot()
    {
        $payload = Helpers::getSanitizedPayload();

        $maxQuery     = "
            SELECT COALESCE(MAX(position), 0) + 1 AS new_position
            FROM lots
            WHERE article = :article
        ";
        $maxParams    = [':article' => $payload['article']];
        $maxStatement = $this->_database->prepareAndExecute($maxQuery, $maxParams);

        $position  = $maxStatement->fetchColumn(0);
        $timestamp = $payload['timestamp'] ?? time();

        $insertQuery  = "
            INSERT INTO lots (
                article, best_before, stock, position, timestamp
            ) VALUES (
                :article, :best_before, :stock, :position, :timestamp
            )
        ";
        $insertParams = [
            ':article'     => $payload['article'],
            ':best_before' => $payload['best_before'],
            ':stock'       => $payload['stock'],
            ':position'    => $position,
            ':timestamp'   => $timestamp,
        ];

        $this->_database->prepareAndExecute($insertQuery, $insertParams);
        Http::sendNoContent();
    }

    /**
     * Updates the lot from payload.
     *
     * @param integer $lotId ID of the lot to update.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 04.09.2020
     */
    public function updateLot(int $lotId)
    {
        $payload = Helpers::getSanitizedPayload();

        if (isset($payload['timestamp'])) {
            $currentQuery  = "SELECT timestamp FROM lots WHERE id = :id";
            $currentParams = [':id' => $lotId];

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
            UPDATE lots SET
                best_before = :best_before,
                stock = :stock,
                timestamp = :timestamp
            WHERE id = :id
        ";
        $updateParams = [
            ':id'          => $lotId,
            ':best_before' => $payload['best_before'],
            ':stock'       => $payload['stock'],
            ':timestamp'   => $timestamp,
        ];

        $this->_database->prepareAndExecute($updateQuery, $updateParams);
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
     * @version 05.08.2020
     */
    public function decrementStock(int $lotId)
    {
        $this->_modifyStock($lotId, "stock - 1");
    }

    /**
     * Increments the lot's stock by one.
     *
     * @param integer $lotId ID of the lot to modify.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    public function incrementStock(int $lotId)
    {
        $this->_modifyStock($lotId, "stock + 1");
    }

    /**
     * Moves the lot one position down.
     *
     * @param integer $lotId ID of the lot to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    public function moveDown(int $lotId)
    {
        $this->_moveLot($lotId, ">", "ASC");
    }

    /**
     * Moves the lot one position up.
     *
     * @param integer $lotId ID of the lot to move.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    public function moveUp(int $lotId)
    {
        $this->_moveLot($lotId, "<", "DESC");
    }

    /**
     * Shorthand method for modifying the lot's stock.
     *
     * @param integer $lotId    ID of the lot to modify.
     * @param string  $newStock Stock's new value.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    private function _modifyStock(int $lotId, string $newStock)
    {
        $query  = "
            UPDATE lots SET
                stock = ".$newStock.",
                timestamp = :timestamp
            WHERE id = :id
        ";
        $params = [
            ':id'        => $lotId,
            ':timestamp' => time(),
        ];

        $this->_database->prepareAndExecute($query, $params);

        $responseQuery     = "
            SELECT *
            FROM lots
            WHERE id = :id
        ";
        $responseParams    = [':id' => $lotId];
        $responseStatement = $this->_database->prepareAndExecute($responseQuery, $responseParams);
        $lot               = $responseStatement->fetch();

        Http::sendJsonResponse($lot);
    }

    /**
     * Moves the lot one position up or down.
     *
     * @param integer $lotId           ID of the lot to move.
     * @param string  $compareOperator Comparator used to find the other lot to swap with
     *                                 (">" or "<" for down or up respectively).
     * @param string  $sortDirection   Sort direction used to find the other lot to swap with
     *                                 ("ASC" or "DESC" for down or up respectively).
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 05.08.2020
     */
    private function _moveLot(int $lotId, string $compareOperator, string $sortDirection)
    {
        $queryThisQuery  = "
            SELECT article, position
            FROM lots
            WHERE id = :id
        ";
        $queryThisParams = [':id' => $lotId];

        $thisStatement = $this->_database->prepareAndExecute($queryThisQuery, $queryThisParams);
        $thisLot       = $thisStatement->fetch();

        $queryOtherQuery  = "
            SELECT id, position
            FROM lots
            WHERE article = :article
                AND position ".$compareOperator." :position
            ORDER BY position ".$sortDirection."
            LIMIT 1
        ";
        $queryOtherParams = [
            ':article'  => $thisLot['article'],
            ':position' => $thisLot['position'],
        ];

        $otherStatement = $this->_database->prepareAndExecute($queryOtherQuery, $queryOtherParams);
        $otherLot       = $otherStatement->fetch();

        $moveThisQuery     = "
            UPDATE lots SET
                position = :position,
                timestamp = :timestamp
            WHERE id = :id
        ";
        $moveThisStatement = $this->_database->prepare($moveThisQuery);

        $moveThisParams = [
            ':id'        => $lotId,
            ':position'  => $otherLot['position'],
            ':timestamp' => time(),
        ];
        $moveThisStatement->execute($moveThisParams);

        $moveOtherQuery     = "
            UPDATE lots SET
                position = :position
            WHERE id = :id
        ";
        $moveOtherStatement = $this->_database->prepare($moveOtherQuery);

        $moveOtherParams = [
            ':id'       => $otherLot['id'],
            ':position' => $thisLot['position'],
        ];
        $moveOtherStatement->execute($moveOtherParams);

        $responseQuery     = "
            SELECT *
            FROM lots
            WHERE id IN(:thisId, :otherId)
        ";
        $responseParams    = [
            ':thisId'  => $lotId,
            ':otherId' => $otherLot['id'],
        ];
        $responseStatement = $this->_database->prepareAndExecute($responseQuery, $responseParams);
        $lots              = $responseStatement->fetchAll();

        $response = ['lots' => $lots];
        Http::sendJsonResponse($response);
    }
}


// Útƒ-8 encoded
