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
$config = new \App\Support\Config(require_once __DIR__ . '/../config/config.php');

// this is support for "old" configuration through 'config.inc.php' file
if (file_exists(__DIR__.'/../inc/config.inc.php')) {
    $config->setFromConstants();
}
