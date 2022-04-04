<?php

/**
 * Class Article.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 04.04.2022
 */
namespace Rhorber\Inventory\API\V3\Entities;

/**
 * Article entity. An article is an item, optionally with its lots and GTINs.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 04.04.2022
 */
class Article extends Entity
{
    /**#@+
     * @access public
     * @var    integer
     */
    /** ID of the article. */
    public $id;
    /** Category ID of the article. */
    public $category;
    /**#@-*/
    /**
     * Name of the article.
     *
     * @access public
     * @var    string
     */
    public $name;
    /**
     * Size of the article.
     *
     * @access public
     * @var    float
     */
    public $size;
    /**
     * Unit of the article's size.
     *
     * @access public
     * @var    string
     */
    public $unit;
    /**#@+
     * @access public
     * @var    integer
     */
    /** Inventory (stocktaking) status of the article. */
    public $inventoried;
    /** Position of the article. */
    public $position;
    /** Last updated timestamp of the article. */
    public $timestamp;
    /**#@-*/
    /**
     * Lots of the article. See {@see Lot}.
     *
     * @access public
     * @var    Lot[]
     */
    public $lots;
    /**
     * GTINs of the article.
     *
     * @access public
     * @var    string[]
     */
    public $gtins;


    /**
     * Maps/processes the query result row to an entity/object instance.
     *
     * @param array $row Query result row to process.
     *
     * @return  Article An instance of the entity with the parsed properties.
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2022
     */
    public static function mapToEntity(array $row)
    {
        $article              = new Article();
        $article->id          = intval($row['id']);
        $article->category    = intval($row['category']);
        $article->name        = $row['name'];
        $article->size        = floatval($row['size']);
        $article->unit        = $row['unit'];
        $article->inventoried = intval($row['inventoried']);
        $article->position    = intval($row['position']);
        $article->timestamp   = intval($row['timestamp']);
        $article->lots        = [];

        if (empty($row['lots']) === false) {
            $article->lots = Lot::mapToEntities($row['lots']);
        }

        if (isset($row['gtins']) === true) {
            $article->gtins = $row['gtins'];
        }

        return $article;
    }
}
