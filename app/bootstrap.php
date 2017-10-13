<?php

/*
 * Load Composer's autoload
 * ------------------------
 *
 */

require __DIR__.'/../vendor/autoload.php';

/*
 * Load environmental variables from .env
 * --------------------------------------
 *
 */

$envFile = __DIR__.'/../.env';
if (file_exists($envFile)) {
    (new Dotenv\Dotenv(dirname($envFile)))->load();
}

/*
 * Load the configuration
 * ----------------------
 *
 */
$config = require_once __DIR__ . '/../config/config.php';
