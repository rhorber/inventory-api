<?php

/**
 * Class GtinController.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 04.04.2022
 */
namespace Rhorber\Inventory\API\V3;

use Rhorber\Inventory\API\Database;
use Rhorber\Inventory\API\Http;


/**
 * Class for handling GTIN requests. All methods terminate execution.
 *
 * @package Rhorber\Inventory\API\V3
 * @author  Raphael Horber
 * @version 04.04.2022
 */
class GtinController
{
    /**
     * Database connection.
     *
     * @access private
     * @var    Database
     */
    private $_database;


    /**
     * Constructor: Connects to the database.
     *
     * @access  public
     * @author  Raphael Horber
     * @version 14.11.2021
     */
    public function __construct()
    {
        $this->_database = new Database();
    }

    /**
     * Queries the passed GTIN.
     *
     * If a stored article has it stored, the article will be returned.
     * Otherwise, the Open-Food-Facts API will be queried for the relevant properties.
     *
     * @param string $gtin GTIN to search.
     *
     * @return  void
     * @access  public
     * @author  Raphael Horber
     * @version 04.04.2022
     */
    public function query(string $gtin)
    {
        $query     = "
            SELECT article
            FROM gtins
            WHERE gtin = :gtin
        ";
        $params    = [
            ':gtin' => $gtin,
        ];
        $statement = $this->_database->prepareAndExecute($query, $params);

        if ($statement->rowCount() === 1) {
            $articleId = $statement->fetchColumn(0);

            $response = [
                'type'      => "existing",
                'articleId' => intval($articleId),
            ];
        } else {
            $response = $this->_queryOpenFoodFactsApi($gtin);
        }

        Http::sendJsonResponse($response);
    }

    /**
     * Queries the Open Food Facts API with the passed GTIN.
     *
     * Searches in the CH database for the product, if not found queries also the world database.
     *
     * @param string $gtin GTIN to query for.
     *
     * @return  array Response array with additional data, either of type "found", "notFound", or "error".
     * @access  private
     * @author  Raphael Horber
     * @version 14.11.2021
     */
    private function _queryOpenFoodFactsApi(string $gtin)
    {
        try {
            $response = $this->_sendOpenFoodFactsRequest($gtin, "ch");

            // Product not found for country, search in "world".
            if ($response->status === 0) {
                $response = $this->_sendOpenFoodFactsRequest($gtin, "world");
            }

            // Product still not found.
            if ($response->status === 0) {
                return [
                    'type' => "notFound",
                ];
            }

            $name = ($response->product->product_name_de !== "")
                ? $response->product->product_name_de
                : $response->product->product_name;

            return [
                'type'     => "found",
                'name'     => $name,
                'quantity' => $response->product->quantity,
            ];
        } catch (\Exception $exception) {
            return [
                'type'  => "error",
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Sends a request to the Open Food Facts API with the passed GTIN and country code.
     *
     * @param string $gtin    GTIN to query for.
     * @param string $country Country code to query.
     *
     * @return  \stdClass API response, decoded.
     * @access  private
     * @throws  \Exception If a cURL error occurred.
     * @version 14.11.2021
     * @author  Raphael Horber
     */
    private function _sendOpenFoodFactsRequest(string $gtin, string $country)
    {
        $baseUrl = "https://".$country.".openfoodfacts.org/api/v0/product/";
        $url     = $baseUrl.$gtin.".json?fields=product_name,product_name_de,quantity";

        $userAgent = "User-Agent: rhorber/inventory - Web - https://github.com/rhorber/inventory-gui";

        $queryOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => [$userAgent],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_TIMEOUT        => 30,
        ];

        $handle = curl_init();
        curl_setopt_array($handle, $queryOptions);

        $response    = curl_exec($handle);
        $error       = curl_error($handle);
        $errorNumber = curl_errno($handle);
        curl_close($handle);

        if ($error !== "") {
            throw new \Exception("cURL error: #".$errorNumber.": ".$error);
        }

        return json_decode($response);
    }
}


// Útƒ-8 encoded
