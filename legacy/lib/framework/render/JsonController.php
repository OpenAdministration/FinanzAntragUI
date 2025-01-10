<?php

/**
 * FRAMEWORK JsonHandler
 *
 * @category        framework
 *
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 *
 * @since 			17.02.2018
 *
 * @copyright 		Copyright (C) 2018 - All rights reserved
 *
 * @platform        PHP
 *
 * @requirements    PHP 7.0 or higher
 */

namespace framework\render;

use App\Exceptions\LegacyJsonException;

class JsonController
{
    /**
     * json result of the function
     *
     * @var array
     */
    protected $json_result;

    // ================================================================================================

    /**
     * private class constructor
     * implements singleton pattern
     */
    public function __construct() {}

    /**
     * dummy function for inheritance
     * so mother controller may implements a translator pattern
     */
    public function translate($in)
    {
        return $in;
    }

    // ================================================================================================

    /**
     * returns 403 access denied in json format
     */
    public function json_access_denied($message = false)
    {
        http_response_code(403);
        $this->json_result = ['success' => false, 'eMsg' => $this->translate(($message) ?: 'Access Denied.')];
        $this->print_json_result();
    }

    /**
     * returns 404 not found in html format
     *
     * @param  false|string  $message  (optional) error message
     */
    public function json_not_found($message = false)
    {
        http_response_code(404);
        $this->json_result = ['success' => false, 'eMsg' => $this->translate(($message) ? $message : 'Page not Found.')];
        $this->print_json_result();
    }

    /**
     * echo json result  stored in $this->json_result
     *
     * @param  bool  $jsonHeader,  default: false
     */
    protected function print_json_result($jsonHeader = false): void
    {
        self::print_json($this->json_result, $jsonHeader);
    }

    /**
     * @param  array  $json  json data
     * @param  bool  $jsonHeader,  default: true
     */
    public static function print_json(array $json, $jsonHeader = true): void
    {
        // $json = json_encode($json, JSON_HEX_QUOT | JSON_HEX_TAG);
        throw new LegacyJsonException($json);
    }
}
