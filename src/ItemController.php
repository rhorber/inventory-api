<?php

/**
 * Class ItemController.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.12.2018
 */
namespace Rhorber\Inventory\API;


/**
 * Class for modifying or adding an item. All methods terminate execution.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.12.2018
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
     * @version 01.12.2018
     */
    public function addItem()
    {
        $query  = "
            INSERT INTO items (
                name, stock, size, unit
            ) VALUES (
                :name, :stock, :size, :unit
            )
        ";
        $params = [
            ':name'  => "N/A",
            ':stock' => 0,
            ':size'  => 0,
            ':unit'  => "N/A",
        ];

        $this->_modifyItem($query, $params);
    }

    /**
     * Updates the item from payload.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 01.12.2018
     */
    public function updateItem()
    {
        $query  = "
            UPDATE items SET
                name = :name,
                stock = :stock,
                size = :size,
                unit = :unit
            WHERE id = :id
        ";
        $params = [
            ':id'    => $this->_itemId,
            ':name'  => "N/A",
            ':stock' => 0,
            ':size'  => 0,
            ':unit'  => "N/A",
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
     * @version 01.12.2018
     */
    private function _modifyItem(string $query, array $params)
    {
        $json = Helpers::getSanitizedPayload();

        foreach (["name", "stock", "size", "unit"] as $param) {
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
}


// Útƒ-8 encoded
