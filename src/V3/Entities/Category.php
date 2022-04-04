<?php

/**
 * Class Category.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 04.04.2022
 */
namespace Rhorber\Inventory\API\V3\Entities;

/**
 * Category entity. A category is a group of articles/items.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 04.04.2022
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
     * Maps/processes the query result row to an entity/object instance.
     *
     * @param array $row Query result row to process.
     *
     * @return  Category An instance of the entity with the parsed properties.
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2022
     */
    public static function mapToEntity(array $row)
    {
        $category            = new Category();
        $category->id        = intval($row['id']);
        $category->name      = $row['name'];
        $category->position  = intval($row['position']);
        $category->timestamp = intval($row['timestamp']);

        return $category;
    }
}
