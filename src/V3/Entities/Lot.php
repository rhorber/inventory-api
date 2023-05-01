<?php

/**
 * Class Lot.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API\V3\Entities;

use MongoDB\Model\BSONDocument;


/**
 * Lot entity. A lot is the items of an article with the same (or unknown) best before date.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 01.05.2023
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
     * Maps/processes the query result document to an entity/object instance.
     *
     * @param BSONDocument $document Query result document to process.
     *
     * @return  Lot A `Lot` entity instance with the parsed properties.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public static function mapToEntity(BSONDocument $document)
    {
        $lot              = new Lot();
        $lot->id          = $document['_id'];
        $lot->article     = $document['article'];
        $lot->best_before = $document['bestBefore'];
        $lot->stock       = $document['stock'];
        $lot->position    = $document['position'];
        $lot->timestamp   = $document['timestamp'];

        return $lot;
    }
}
