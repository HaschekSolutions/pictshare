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

if ($forceDomain = $app->getContainer()->get(\App\Support\ConfigInterface::class)->get('app.force_domain')) {
    define('DOMAINPATH', $forceDomain);
} else {
    define('DOMAINPATH', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') .
                         '://' . $_SERVER['HTTP_HOST']);
}


$cliController = $app->getContainer()->get(\App\Controllers\CliController::class);
$cliController->processCommand($argv);
