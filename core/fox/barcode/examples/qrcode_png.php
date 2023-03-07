<?php

require dirname(__FILE__).'/../vendor/autoload.php';

use fox\barcode\QRcode;

QRcode::png('https://github.com/jucksearm/php-barcode');