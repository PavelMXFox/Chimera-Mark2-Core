#!/usr/bin/php
<?php 

/**
 *
 * Script flushExpiredConfirmationCodes.php
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

use fox\confirmCode;

if (php_sapi_name() != 'cli') {
    throw new Exception("This script can be run via CLI only");
}

require_once(getenv("FOX_WEBROOT")."/Autoloader.php");

confirmCode::flushExpired();