<?php

/**
 * Class Entity.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 01.05.2023
 */
namespace Rhorber\Inventory\API\V3\Entities;

use MongoDB\Model\BSONDocument;


/**
 * Class Entity. Base class for entities.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 01.05.2023
 */
abstract class Entity
{
    /**
     * Maps/processes the query result document to an entity/object instance.
     *
     * @param BSONDocument $document Query result document to process.
     *
     * @return  Entity An instance of the entity with the parsed properties.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public static abstract function mapToEntity(BSONDocument $document);

    /**
     * Maps the query result contained in a cursor to entity/object instances.
     *
     * @param iterable $iterable Result iterable ({@see \MongoDB\Driver\Cursor} or array) containing the query result.
     *
     * @return  $this[] An array of entities, created from the result rows.
     * @access  public
     * @author  Raphael Horber
     * @version 01.05.2023
     */
    public static function mapToEntities(iterable $iterable)
    {
        $entities = [];

        foreach ($iterable as $document) {
            $entities[] = static::mapToEntity($document);
        }

        return $entities;
    }
}
