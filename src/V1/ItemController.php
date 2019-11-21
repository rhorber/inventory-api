<?php

/**
 * Class ItemController.
 *
 * @package Rhorber\Inventory\API\V1
 * @author  Raphael Horber
 * @version 21.11.2019
 */
namespace Rhorber\Inventory\API\V1;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Helpers;
use Rhorber\Inventory\API\Http;


/**
 * Class for modifying or adding an item. All methods terminate execution.
 *
 * @package Rhorber\Inventory\API\V1
 * @author  Raphael Horber
 * @version 16.11.2019
 */
class ItemController
{
    /**
     * Database connection.
     *
     * @access private
     * @var    Database
     */
    private $_database;

    /**
     * ID of the item to modify.
     *
     * @access private
     * @var    integer|null
     */
    private $_itemId;


    /**
     * Constructor: Saves the item's ID and connects to the database.
     *
     * @param integer|null $itemId ID of the item to modify (`null` for adding a new one).
     *
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public function __construct($itemId)
    {
        $this->_itemId   = $itemId;
        $this->_database = new Database();
    }

    /**
     * Returns the item as JSON response.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public function returnItem()
    {
        $query  = "SELECT * FROM items WHERE id = :id";
        $params = [':id' => $this->_itemId];

        $statement = $this->_database->prepareAndExecute($query, $params);
        $item      = $statement->fetch();

        Http::sendJsonResponse($item);
    }

    /**
     * Adds a new item from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 16.11.2019
     */
    public function addItem()
    {
        $maxQuery  = "
            SELECT COALESCE(MAX(position), 0) + 1 AS new_position
            FROM items
        ";
        $maxResult = $this->_database->queryAndFetch($maxQuery);
        $position  = $maxResult[0]['new_position'];

        $insertQuery = "
            INSERT INTO items (
                name, size, unit, best_before, stock, position
            ) VALUES (
                :name, :size, :unit, :best_before, :stock, :position
            )
        ";
        $params      = [
            ':name'        => "N/A",
            ':size'        => 0,
            ':unit'        => "N/A",
            ':best_before' => '',
            ':stock'       => 0,
            ':position'    => $position,
        ];

        $this->_modifyItem($insertQuery, $params);
    }

    /**
     * Updates the item from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 16.11.2019
     */
    public function updateItem()
    {
        $query  = "
            UPDATE items SET
                name = :name,
                size = :size,
                unit = :unit,
                best_before = :best_before,
                stock = :stock
            WHERE id = :id
        ";
        $params = [
            ':id'          => $this->_itemId,
            ':name'        => "N/A",
            ':size'        => 0,
            ':unit'        => "N/A",
            ':best_before' => '',
            ':stock'       => 0,
        ];

        $this->_modifyItem($query, $params);
    }

    /**
     * Decrements the item's stock by one.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public function decrementStock()
    {
        $this->_modifyStock("stock - 1");
    }

    /**
     * Increments the item's stock by one.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public function incrementStock()
    {
        $this->_modifyStock("stock + 1");
    }

    /**
     * Resets the item's stock (sets it to zero).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public function resetStock()
    {
        $this->_modifyStock("0");
    }

    /**
     * Moves the item one position down.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 02.12.2018
     */
    public function moveDown()
    {
        $this->_moveItem("+", "-");
    }

    /**
     * Moves the item one position up.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 02.12.2018
     */
    public function moveUp()
    {
        $this->_moveItem("-", "+");
    }

    /**
     * Insert or update an item.
     *
     * - Gets the request's payload
     * - modifies the passed params with it
     * - and executes the passed query with the modified params
     *
     * @param string $query  Query to execute (INSERT or UPDATE).
     * @param array  $params Query's default params.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 16.11.2019
     */
    private function _modifyItem(string $query, array $params)
    {
        $json = Helpers::getSanitizedPayload();

        foreach (["name", "size", "unit", "best_before", "stock"] as $param) {
            if (empty($json[$param]) === false) {
                $params[':'.$param] = $json[$param];
            }
        }

        $this->_database->prepareAndExecute($query, $params);
        Http::sendNoContent();
    }

    /**
     * Shorthand method for modifying the item's stock.
     *
     * @param string $newStock Stock's new value.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    private function _modifyStock(string $newStock)
    {
        $query  = "UPDATE items SET stock = ".$newStock." WHERE id = :id";
        $params = [':id' => $this->_itemId];

        $this->_database->prepareAndExecute($query, $params);
        Http::sendNoContent();
    }

    /**
     * Moves the item one position up or down.
     *
     * @param string $thisDirection  Direction of this item ("+" or "-" for down or up respectively).
     * @param string $otherDirection Direction of the other item ("-" or "+" for down or up respectively).
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 02.12.2018
     */
    private function _moveItem($thisDirection, $otherDirection)
    {
        $query  = "SELECT position FROM items WHERE id = :id";
        $params = [':id' => $this->_itemId];

        $statement = $this->_database->prepareAndExecute($query, $params);
        $thisItem  = $statement->fetch();
        $position  = $thisItem['position'];

        $moveOther   = "
            UPDATE items SET
                position = position ".$otherDirection." 1
            WHERE position = :position ".$thisDirection." 1
        ";
        $paramsOther = [':position' => $position];
        $this->_database->prepareAndExecute($moveOther, $paramsOther);

        $moveThis = "
            UPDATE items SET
                position = position ".$thisDirection." 1
            WHERE id = :id
        ";
        $this->_database->prepareAndExecute($moveThis, $params);

        Http::sendNoContent();
    }
}


// Útƒ-8 encoded
