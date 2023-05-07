<?php

/**
 * Class Category.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API\V3\Entities;

use MongoDB\Model\BSONDocument;


/**
 * Category entity. A category is a group of articles/items.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 01.05.2023
 */
class Category extends Entity
{
    /**
     * ID of the category.
     *
     * @access public
     * @var    integer
     */
    public $id;
    /**
     * Name of the category.
     *
     * @access public
     * @var    string
     */
    public $name;
    /**#@+
     * @access public
     * @var    integer
     */
    /** Position of the category. */
    public $position;
    /** Last updated timestamp of the category. */
    public $timestamp;
    /**#@-*/


    /**
     * Maps/processes the query result document to an entity/object instance.
     *
     * @param BSONDocument $document Query result document to process.
     *
     * @return  Category A `Category` entity instance with the parsed properties.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public static function mapToEntity(BSONDocument $document)
    {
        $category            = new Category();
        $category->id        = $document['_id'];
        $category->name      = $document['name'];
        $category->position  = $document['position'];
        $category->timestamp = $document['timestamp'];

        return $category;
    }
}
