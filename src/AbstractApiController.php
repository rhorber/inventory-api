<?php

/**
 * Class AbstractApiController.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 21.11.2019
 */
namespace Rhorber\Inventory\API;


/**
 * Base class for API controllers.
 *
 * Handles CORS, validates authorization, parses the URI, and saves the segments in the protected properties.
 * Request URI structure: `/api/v:version/:entity[/:id[/:action]]`
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 21.11.2019
 */
abstract class AbstractApiController
{
    /**
     * Full request uri (`$_SERVER['REQUEST_URI']`).
     *
     * @access protected
     * @var    string
     */
    protected $uri;

    /**
     * Request method (`$_SERVER['REQUEST_METHOD']`).
     *
     * @access protected
     * @var    string
     */
    protected $method;

    /**
     * Request's entity segment.
     *
     * @access protected
     * @var    string
     */
    protected $entity;

    /**
     * Request's id segment.
     *
     * @access protected
     * @var    string
     */
    protected $entityId;

    /**
     * Request's action segment.
     *
     * @access protected
     * @var    string
     */
    protected $action;


    /**
     * Constructor: Handles CORS, validates authorization, and parses the URI.
     *
     * @access  protected
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    protected function __construct()
    {
        $this->uri    = $_SERVER['REQUEST_URI'];
        $this->method = mb_strtoupper($_SERVER['REQUEST_METHOD']);

        Http::handleCors($this->method);
        Authorization::verifyAuth();

        $this->_parseUri();
    }

    /**
     * Parses the URI (sets the protected segment properties).
     *
     * @return  void
     * @access  private
     * @author  Raphael Horber
     * @version 21.11.2019
     */
    private function _parseUri()
    {
        $uri = mb_substr($this->uri, 8);

        $pathParts = explode("/", $uri);
        if (count($pathParts) > 0) {
            $this->entity = $pathParts[0];
        }
        if (count($pathParts) > 1) {
            $this->entityId = $pathParts[1];
        }
        if (count($pathParts) > 2) {
            $this->action = $pathParts[2];
        }

        if ($this->entityId !== null && intval($this->entityId) === 0) {
            Http::sendNotFound();
        }
    }
}


// Útƒ-8 encoded
