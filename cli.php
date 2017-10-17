<?php

/*
 * Bootstrap the application
 * -------------------------
 *
 */

/**
 * @var \App\Application $app
 */
$app = require __DIR__.'/app/bootstrap.php';


define('ROOT', dirname(__FILE__));
define('CLI', true);
$path = ((dirname($_SERVER['PHP_SELF']) == '/' ||
          dirname($_SERVER['PHP_SELF']) == '\\' ||
          dirname($_SERVER['PHP_SELF']) == '/index.php' ||
          dirname($_SERVER['PHP_SELF']) == '/backend.php') ? '/' : dirname($_SERVER['PHP_SELF']) . '/');
define('PATH', $path);

if ($forceDomain = $config->get('app.force_domain')) {
    define('DOMAINPATH', $forceDomain);
} else {
    define('DOMAINPATH', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') .
                         '://' . $_SERVER['HTTP_HOST']);
}


$cliController = new \App\Controllers\CliController(new \App\Models\PictshareModel());
$cliController->processCommand($argv);
