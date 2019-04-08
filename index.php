<?php

/**
 * Index page: Sets up environment and delegates request handling.
 *
 * @package Rhorber\Inventory\API
 * @author  Raphael Horber
 * @version 05.04.2019
 */
namespace Rhorber\Inventory\API;


/** Include Loader. */
require_once __DIR__.'/vendor/autoload.php';

Helpers::loadEnvFile();
Helpers::validateEnvVariables();

Authorization::verifyAuth();

ApiController::handleRequest();


// Útƒ-8 encoded
