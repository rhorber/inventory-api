<?php

/**
 * Class Article.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API\V3\Entities;

use MongoDB\Model\BSONDocument;


/**
 * Article entity. An article is an item, optionally with its lots and GTINs.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 01.05.2023
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
     * Maps/processes the query result document to an entity/object instance.
     *
     * @param BSONDocument $document Query result document to process.
     *
     * @return  Article An `Article` entity instance with the parsed properties.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public static function mapToEntity(BSONDocument $document)
    {
        $article              = new Article();
        $article->id          = $document['_id'];
        $article->category    = $document['category'];
        $article->name        = $document['name'];
        $article->size        = $document['size'];
        $article->unit        = $document['unit'];
        $article->inventoried = $document['inventoried'];
        $article->position    = $document['position'];
        $article->timestamp   = $document['timestamp'];
        $article->lots        = [];
        $article->gtins       = $document['gtins'];

        if (empty($document['lots']) === false) {
            $article->lots = Lot::mapToEntities($document['lots']);
        }

        return $article;
    }
}
