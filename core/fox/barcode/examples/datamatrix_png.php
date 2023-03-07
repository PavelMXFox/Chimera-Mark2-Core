<?php

require dirname(__FILE__).'/../vendor/autoload.php';

use fox\barcode\Datamatrix;

Datamatrix::png('https://github.com/jucksearm/php-barcode');