<?php

/**
 * CONTROLLER FileHandler
 *
 * @category        framework
 *
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 *
 * @since 			08.05.2018
 *
 * @copyright 		Copyright (C) 2018 - All rights reserved
 *
 * @platform        PHP
 *
 * @requirements    PHP 7.0 or higher
 */

namespace framework\file;

use App\Exceptions\LegacyDieException;
use framework\DBConnector;
use Illuminate\Support\Facades\Storage;

class FileController
{
    /**
     * @var DBConnector
     */
    private $db;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->db = DBConnector::getInstance();
    }

    public function handle($routeInfo): void
    {
        if ($routeInfo['action'] === 'get') {
            if (! isset($routeInfo['fdl'])) {
                $routeInfo['fdl'] = 0;
            }
            $this->get($routeInfo);
        }
    }

    /**
     * ACTION get
     * handle file delivery
     */
    private function get($routeInfo): void
    {
        $fh = new FileHandler($this->db);
        // get file
        $file = $fh->checkFileHash($routeInfo['key']);
        if (! $file) {
            throw new LegacyDieException(404);

            return;
        }
        // TODO FIXME ACL - user has permission to download/view this file?
        if (false) {// !checkUserPermission($top['gname'])) {
            throw new LegacyDieException(403);
            exit();
        }
        // old:
        // $fh->deliverFileData($file, $routeInfo['fdl']);
        if (Storage::exists(FileHandler::getDiskpathOfFile($file))) {
            echo 'data:application/pdf;base64,';
            echo base64_encode(Storage::get(FileHandler::getDiskpathOfFile($file)));
        }

    }
}
