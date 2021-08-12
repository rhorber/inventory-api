<?php

/**
 * Class InventoriesController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 12.08.2021
 */
namespace Rhorber\Inventory\API\V3;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Http;


/**
 * Class for starting or stopping an inventory (stocktaking). All methods terminate execution.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 12.08.2021
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
     * Constructor: Connects to the database.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 12.08.2021
     */
    public function __construct()
    {
        $this->_database = new Database();
    }

    /**
     * Returns whether an inventory (stocktaking) is running ("active") or not ("inactive").
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 12.08.2021
     */
    public function status()
    {
        $query = "
            SELECT COUNT(*) AS count
            FROM inventories
            WHERE stop IS NULL
        ";

        $statement = $this->_database->prepareAndExecute($query, []);
        $count     = $statement->fetchColumn(0);

        $status = ($count > 0) ? "active" : "inactive";

        $response = ['status' => $status];
        Http::sendJsonResponse($response);
    }

    /**
     * Starts a new inventory (stocktaking).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 12.08.2021
     */
    public function start()
    {
        $this->_updateAllArticles(0);

        $query  = "
            INSERT INTO inventories (
                start, stop
            ) VALUES (
                :timestamp, NULL
            )
        ";
        $params = [
            ':timestamp' => time(),
        ];
        $this->_database->prepareAndExecute($query, $params);

        Http::sendNoContent();
    }

    /**
     * Stops the current inventory (stocktaking).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 12.08.2021
     */
    public function stop()
    {
        $this->_updateAllArticles(-1);

        $inventoryQuery  = "
            UPDATE inventories SET
                stop = :timestamp
            WHERE stop IS NULL
        ";
        $inventoryParams = [
            ':timestamp' => time(),
        ];
        $this->_database->prepareAndExecute($inventoryQuery, $inventoryParams);

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
     * @version 12.08.2021
     */
    private function _updateAllArticles(int $status)
    {
        $query  = "
            UPDATE articles SET
                inventoried = :status
        ";
        $params = [':status' => $status];

        $this->_database->prepareAndExecute($query, $params);
    }
}


// Útƒ-8 encoded
