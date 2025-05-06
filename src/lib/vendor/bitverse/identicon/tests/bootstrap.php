<?php

$loader = __DIR__.'/../vendor/autoload.php';

if (!file_exists($loader)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

require_once $loader;
