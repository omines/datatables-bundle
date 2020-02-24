<?php

$autoload = __DIR__.'/../../../vendor/autoload.php';

if (!file_exists($autoload)) {
    echo 'You should run "composer install" in the library root before running this script.';
}

require_once $autoload;
