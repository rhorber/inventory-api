<?php

/**
 * Index page: Sets up environment and delegates request handling.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 01.12.2018
 */
namespace Rhorber\Inventory\API;


/** Include Loader. */
require_once __DIR__.'/vendor/autoload.php';

Helpers::loadEnvFile();

ApiController::handleRequest();


// Útƒ-8 encoded
