<?php

/**
 * Class InventoriesController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API\V3;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Http;


/**
 * Class for starting or stopping an inventory (stocktaking). All methods terminate execution.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 01.05.2023
 */
class InventoriesController
{
    /**
     * Database connection.
     *
     * @access private
     * @var    Database
     */
    private $_database;

    /**
     * Collection `inventories` of the database.
     *
     * @access private
     * @var    \MongoDB\Collection
     */
    private $_inventories;


    /**
     * Returns whether an inventory (stocktaking) is active/running.
     *
     * @param Database $database Database connection.
     *
     * @return  boolean Whether an inventory is active.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public static function isInventoryActive(Database $database): bool
    {
        $inventory = $database->inventories->findOne(
            ['stop' => ['$exists' => false]]
        );

        return ($inventory !== null);
    }

    /**
     * Initializes a new instance of the `InventoriesController` class.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function __construct()
    {
        $this->_database    = new Database();
        $this->_inventories = $this->_database->inventories;
    }

    /**
     * Returns whether an inventory (stocktaking) is running ("active") or not ("inactive").
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function status()
    {
        $status = (self::isInventoryActive($this->_database))
            ? "active"
            : "inactive";

        $response = ['status' => $status];

        Http::sendJsonResponse($response);
    }

    /**
     * Starts a new inventory (stocktaking).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function start()
    {
        $this->_updateAllArticles(0);

        $document = [
            'start' => $this->_database->nowDateTime,
        ];
        $this->_inventories->insertOne($document);

        Http::sendNoContent();
    }

    /**
     * Stops the current inventory (stocktaking).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public function stop()
    {
        $this->_updateAllArticles(-1);

        $updateFields = [
            'stop' => $this->_database->nowDateTime,
        ];
        $this->_inventories->updateOne(
            ['stop' => ['$exists' => false]],
            ['$set' => $updateFields]
        );

        Http::sendNoContent();
    }

    /**
     * Sets inventoried status of all articles to the passed one.
     *
     * @param integer $status Status to set.
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    private function _updateAllArticles(int $status)
    {
        $updateFields = [
            'inventoried' => $status,
        ];
        $this->_database->articles->updateMany(
            [],
            ['$set' => $updateFields]
        );
    }
}


// Útƒ-8 encoded
