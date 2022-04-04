<?php

/**
 * Class Entity.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 04.04.2022
 */
namespace Rhorber\Inventory\API\V3\Entities;

/**
 * Class Entity. Base class for entities.
 *
 * @package Rhorber\Inventory\API\V3\Entities
 * @author  Raphael Horber
 * @version 04.04.2022
 */
abstract class Entity
{
    /**
     * Maps/processes the query result row to an entity/object instance.
     *
     * @param array $row Query result row to process.
     *
     * @return  Entity An instance of the entity with the parsed properties.
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2022
     */
    public static abstract function mapToEntity(array $row);

    /**
     * Maps/processes multiple query result rows to entity/object instances.
     *
     * @param array $rows Query result rows to process.
     *
     * @return  $this[] An array of entities, created from the result rows.
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2022
     */
    public static function mapToEntities(array $rows)
    {
        return array_map('static::mapToEntity', $rows);
    }
}
