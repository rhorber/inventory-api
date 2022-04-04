<?php

/**
 * Class Lot.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 04.04.2022
 */
namespace Rhorber\Inventory\API\V3\Entities;

/**
 * Lot entity. A lot is the items of an article with the same (or unknown) best before date.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 04.04.2022
 */
class Lot extends Entity
{
    /**#@+
     * @access public
     * @var    integer
     */
    /** ID of the lot. */
    public $id;
    /** Article ID of the lot. */
    public $article;
    /**#@-*/
    /**
     * Best before date of the lot.
     *
     * @access public
     * @var    string
     */
    public $best_before;
    /**#@+
     * @access public
     * @var    integer
     */
    /** Stock (number of articles) of the lot. */
    public $stock;
    /** Position of the lot. */
    public $position;
    /** Last updated timestamp of the lot. */
    public $timestamp;
    /**#@-*/


    /**
     * Maps/processes the query result row to an entity/object instance.
     *
     * @param array $row Query result row to process.
     *
     * @return  Lot An instance of the entity with the parsed properties.
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2022
     */
    public static function mapToEntity(array $row)
    {
        $lot              = new Lot();
        $lot->id          = intval($row['id']);
        $lot->article     = intval($row['article']);
        $lot->best_before = $row['best_before'];
        $lot->stock       = intval($row['stock']);
        $lot->position    = intval($row['position']);
        $lot->timestamp   = intval($row['timestamp']);

        return $lot;
    }
}
